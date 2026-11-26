<?php

declare(strict_types=1);

namespace App\UseCase\Todo;

use App\Domain\DTO\DataModel\Todo\TodoDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Output\Todo\TodoDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\Todo\TodoOutputFactory;
use App\Domain\Gateway\Persister\Todo\TodoPersisterGateway;
use App\Domain\Gateway\Provider\Todo\TodoProviderGateway;
use App\Domain\Registry\Todo\TodoStatusRegistry;
use App\Domain\Validation\Validator\Todo\ArchiveTodoValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * Putting a TODO away. Its tasks show nowhere from then on and nothing in it changes; the persister
 * says it was archived, and its planning entries go (DeleteTodoPlanningEntriesEventHandler).
 */
final readonly class ArchiveTodoUseCase implements UseCaseInterface
{
    public function __construct(
        private ArchiveTodoValidator $validator,
        private TodoProviderGateway $todoProviderGateway,
        private TodoPersisterGateway $todoPersisterGateway,
        private TodoOutputFactory $outputFactory,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(UserDataModel $currentUser, int $todoId): TodoDataOutput
    {
        $todo = $this->todoProviderGateway->findOneById($todoId);
        if (null === $todo) {
            throw new DataModelNotFoundException(TodoDataModel::class);
        }

        $this->validator->validate($currentUser, $todo);

        if (TodoStatusRegistry::ARCHIVED === $todo->status) {
            // Already put away: the caller wanted it archived and it is.
            return $this->outputFactory->buildOne($todo);
        }

        $todo->status = TodoStatusRegistry::ARCHIVED;
        $this->todoPersisterGateway->update($todo);

        return $this->outputFactory->buildOne($todo);
    }
}
