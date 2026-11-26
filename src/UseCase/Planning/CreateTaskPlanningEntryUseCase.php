<?php

declare(strict_types=1);

namespace App\UseCase\Planning;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Planning\SaveTaskPlanningEntryDataInput;
use App\Domain\DTO\Output\Planning\TaskPlanningEntryDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\DataModelFactory\Planning\TaskPlanningEntryDataModelFactory;
use App\Domain\Factory\OutputFactory\Planning\TaskPlanningOutputFactory;
use App\Domain\Gateway\Persister\Planning\TaskPlanningEntryPersisterGateway;
use App\Domain\Gateway\Provider\Category\CategoryProviderGateway;
use App\Domain\Gateway\Provider\Todo\TodoProviderGateway;
use App\Domain\Validation\Validator\Planning\CreateTaskPlanningEntryValidator;
use App\UseCase\UseCaseInterface;
use LogicException;

/**
 * A task model planned on a rule of dates. A single date's task is made at once, and today's if
 * a recurring rule falls today: the persister says the entry was created, and its handler makes
 * them.
 */
final readonly class CreateTaskPlanningEntryUseCase implements UseCaseInterface
{
    public function __construct(
        private CreateTaskPlanningEntryValidator $validator,
        private CategoryProviderGateway $categoryProviderGateway,
        private TodoProviderGateway $todoProviderGateway,
        private TaskPlanningEntryPersisterGateway $entryPersisterGateway,
        private TaskPlanningEntryDataModelFactory $entryFactory,
        private TaskPlanningOutputFactory $outputFactory,
    ) {
    }

    /**
     * @throws ValidationException
     */
    public function execute(UserDataModel $currentUser, SaveTaskPlanningEntryDataInput $input): TaskPlanningEntryDataOutput
    {
        // Whoever it belongs to: someone else's category is refused by the validator, as one not found.
        $category = null === $input->categoryId ? null : $this->categoryProviderGateway->findOneById($input->categoryId);

        // The TODO named — whoever's, refused by the validator as one not found — or the account's default.
        $todo = null === $input->todoId
            ? $this->todoProviderGateway->findDefaultForOwner($currentUser)
            : $this->todoProviderGateway->findOneById($input->todoId);

        $this->validator->validate($currentUser, $input, $category, $todo);

        $entry = $this->entryFactory->build(
            $currentUser,
            $todo ?? throw new LogicException('The validator lets no entry through without its TODO.'),
            $category,
            $input,
        );
        $this->entryPersisterGateway->create($entry);

        return $this->outputFactory->buildOne($entry);
    }
}
