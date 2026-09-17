<?php

declare(strict_types=1);

namespace App\UseCase\Task;

use App\Domain\DTO\DataModel\TaskDataModel;
use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\DTO\Output\Task\TaskDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\TaskOutputFactory;
use App\Domain\Gateway\Persister\TaskPersisterGateway;
use App\Domain\Gateway\Provider\TaskProviderGateway;
use App\Domain\Gateway\Provider\UserProviderGateway;
use App\Domain\Validation\Constraint\Task\TaskClosableConstraint;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;
use DateTimeImmutable;
use Psr\Clock\ClockInterface;

/**
 * Ticking a task off. Never automatic: even when every subtask is done, the person closes the
 * parent themselves.
 */
final readonly class CompleteTaskUseCase implements UseCaseInterface
{
    public const string ERROR_CODE = 'complete_task_invalid';

    public function __construct(
        private UserProviderGateway $userProviderGateway,
        private TaskProviderGateway $taskProviderGateway,
        private TaskPersisterGateway $taskPersisterGateway,
        private TaskOutputFactory $outputFactory,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
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

        if (true === $task->isDone()) {
            // Already ticked off: the caller wanted it done and it is.
            return $this->outputFactory->buildOne($task);
        }

        $violations = TaskClosableConstraint::validate($task);
        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }

        $task->completedAt = DateTimeImmutable::createFromInterface($this->clock->now());
        $this->taskPersisterGateway->update($task);

        return $this->outputFactory->buildOne($task);
    }
}
