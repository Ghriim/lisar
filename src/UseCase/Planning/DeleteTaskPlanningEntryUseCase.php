<?php

declare(strict_types=1);

namespace App\UseCase\Planning;

use App\Domain\DTO\DataModel\Planning\TaskPlanningEntryDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Persister\Planning\TaskPlanningEntryPersisterGateway;
use App\Domain\Gateway\Provider\Planning\TaskPlanningEntryProviderGateway;
use App\Domain\Validation\Validator\Planning\DeleteTaskPlanningEntryValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * A task entry gone, its dates to come with it — a task made ahead and not done. The tasks
 * already fallen stay, done or not, as ordinary tasks.
 */
final readonly class DeleteTaskPlanningEntryUseCase implements UseCaseInterface
{
    public function __construct(
        private DeleteTaskPlanningEntryValidator $validator,
        private TaskPlanningEntryProviderGateway $entryProviderGateway,
        private TaskPlanningEntryPersisterGateway $entryPersisterGateway,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(UserDataModel $currentUser, int $entryId): void
    {
        $entry = $this->entryProviderGateway->findOneById($entryId);
        if (null === $entry) {
            throw new DataModelNotFoundException(TaskPlanningEntryDataModel::class);
        }

        $this->validator->validate($currentUser, $entry);

        $this->entryPersisterGateway->delete($entry);
    }
}
