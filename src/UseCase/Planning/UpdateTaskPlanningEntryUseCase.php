<?php

declare(strict_types=1);

namespace App\UseCase\Planning;

use App\Domain\DTO\DataModel\Planning\TaskPlanningEntryDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Planning\SaveTaskPlanningEntryDataInput;
use App\Domain\DTO\Output\Planning\TaskPlanningEntryDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\DataModelFactory\Planning\TaskPlanningEntryDataModelFactory;
use App\Domain\Factory\OutputFactory\Planning\TaskPlanningOutputFactory;
use App\Domain\Gateway\Persister\Planning\TaskPlanningEntryPersisterGateway;
use App\Domain\Gateway\Provider\Category\CategoryProviderGateway;
use App\Domain\Gateway\Provider\Planning\TaskPlanningEntryProviderGateway;
use App\Domain\Validation\Validator\Planning\UpdateTaskPlanningEntryValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * A task entry replaced whole. What already fell stays as it was — today's task and those before,
 * done or not; the dates to come follow the new model and rule (SyncPlannedTasksEventHandler).
 */
final readonly class UpdateTaskPlanningEntryUseCase implements UseCaseInterface
{
    public function __construct(
        private UpdateTaskPlanningEntryValidator $validator,
        private TaskPlanningEntryProviderGateway $entryProviderGateway,
        private CategoryProviderGateway $categoryProviderGateway,
        private TaskPlanningEntryPersisterGateway $entryPersisterGateway,
        private TaskPlanningEntryDataModelFactory $entryFactory,
        private TaskPlanningOutputFactory $outputFactory,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(UserDataModel $currentUser, int $entryId, SaveTaskPlanningEntryDataInput $input): TaskPlanningEntryDataOutput
    {
        $entry = $this->entryProviderGateway->findOneById($entryId);
        if (null === $entry) {
            throw new DataModelNotFoundException(TaskPlanningEntryDataModel::class);
        }

        $category = match (true) {
            null === $input->categoryId => null,
            $entry->category?->id === $input->categoryId => $entry->category,
            default => $this->categoryProviderGateway->findOneById($input->categoryId),
        };

        $this->validator->validate($currentUser, $entry, $input, $category);

        $this->entryFactory->apply($entry, $category, $input);
        $this->entryPersisterGateway->update($entry);

        return $this->outputFactory->buildOne($entry);
    }
}
