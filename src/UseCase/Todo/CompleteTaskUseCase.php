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
use App\Domain\Validation\Validator\Todo\CompleteTaskValidator;
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
    public function __construct(
        private CompleteTaskValidator $validator,
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
    public function execute(UserDataModel $currentUser, int $taskId): TaskDataOutput
    {
        $task = $this->taskProviderGateway->findOneById($taskId);
        if (null === $task) {
            throw new DataModelNotFoundException(TaskDataModel::class);
        }

        // Validated first, done or not: a task that is someone else's is not handed back either.
        $this->validator->validate($currentUser, $task);

        if (true === $task->isDone()) {
            // Already ticked off: the caller wanted it done and it is.
            return $this->outputFactory->buildOne($task);
        }

        $task->completedAt = DateTimeImmutable::createFromInterface($this->clock->now());
        $this->taskPersisterGateway->update($task);

        return $this->outputFactory->buildOne($task);
    }
}
