<?php

declare(strict_types=1);

namespace App\UseCase\Todo;

use App\Domain\DTO\DataModel\Todo\TaskDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Output\Todo\TaskDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\Todo\TaskOutputFactory;
use App\Domain\Gateway\Persister\Todo\TaskPersisterGateway;
use App\Domain\Gateway\Provider\Todo\TaskProviderGateway;
use App\Domain\Validation\Validator\Todo\ReopenTaskValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * Putting a task back on the list — the way out of a mis-click, and what makes the completed
 * view more than an archive.
 */
final readonly class ReopenTaskUseCase implements UseCaseInterface
{
    public function __construct(
        private ReopenTaskValidator $validator,
        private TaskProviderGateway $taskProviderGateway,
        private TaskPersisterGateway $taskPersisterGateway,
        private TaskOutputFactory $outputFactory,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(UserDataModel $currentUser, int $taskId): TaskDataOutput
    {
        $task = $this->taskProviderGateway->findOneById($taskId);
        if (null === $task) {
            throw new DataModelNotFoundException(TaskDataModel::class);
        }

        $this->validator->validate($currentUser, $task);

        if (false === $task->isDone()) {
            return $this->outputFactory->buildOne($task);
        }

        // Reopening a subtask does not touch its parent: the parent was only closable once
        // every subtask was done, so it simply becomes closable again later.
        $task->completedAt = null;
        $this->taskPersisterGateway->update($task);

        return $this->outputFactory->buildOne($task);
    }
}
