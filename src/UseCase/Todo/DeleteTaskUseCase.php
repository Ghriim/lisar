<?php

declare(strict_types=1);

namespace App\UseCase\Todo;

use App\Domain\DTO\DataModel\Todo\TaskDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Persister\Todo\TaskPersisterGateway;
use App\Domain\Gateway\Provider\Todo\TaskProviderGateway;
use App\Domain\Validation\Validator\Todo\DeleteTaskValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * Deleting means deleting: no archive, and a parent takes its subtasks with it.
 */
final readonly class DeleteTaskUseCase implements UseCaseInterface
{
    public function __construct(
        private DeleteTaskValidator $validator,
        private TaskProviderGateway $taskProviderGateway,
        private TaskPersisterGateway $taskPersisterGateway,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(UserDataModel $currentUser, int $taskId): void
    {
        $task = $this->taskProviderGateway->findOneById($taskId);
        if (null === $task) {
            throw new DataModelNotFoundException(TaskDataModel::class);
        }

        $this->validator->validate($currentUser, $task);

        $this->taskPersisterGateway->delete($task);
    }
}
