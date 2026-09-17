<?php

declare(strict_types=1);

namespace App\UseCase\Task;

use App\Domain\DTO\DataModel\TaskDataModel;
use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\DTO\Output\Task\TaskDataOutput;
use App\Domain\Factory\OutputFactory\TaskOutputFactory;
use App\Domain\Gateway\Persister\TaskPersisterGateway;
use App\Domain\Gateway\Provider\TaskProviderGateway;
use App\Domain\Gateway\Provider\UserProviderGateway;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * Putting a task back on the list — the way out of a mis-click, and what makes the completed
 * view more than an archive.
 */
final readonly class ReopenTaskUseCase implements UseCaseInterface
{
    public function __construct(
        private UserProviderGateway $userProviderGateway,
        private TaskProviderGateway $taskProviderGateway,
        private TaskPersisterGateway $taskPersisterGateway,
        private TaskOutputFactory $outputFactory,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     */
    public function execute(int $ownerId, int $taskId): TaskDataOutput
    {
        $owner = $this->userProviderGateway->findOneById($ownerId);
        if (null === $owner) {
            throw new DataModelNotFoundException(UserDataModel::class);
        }

        $task = $this->taskProviderGateway->findOneByIdForOwner($taskId, $owner);
        if (null === $task) {
            throw new DataModelNotFoundException(TaskDataModel::class);
        }

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
