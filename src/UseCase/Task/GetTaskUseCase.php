<?php

declare(strict_types=1);

namespace App\UseCase\Task;

use App\Domain\DTO\DataModel\TaskDataModel;
use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\DTO\Output\Task\TaskDataOutput;
use App\Domain\Factory\OutputFactory\TaskOutputFactory;
use App\Domain\Gateway\Provider\TaskProviderGateway;
use App\Domain\Gateway\Provider\UserProviderGateway;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

final readonly class GetTaskUseCase implements UseCaseInterface
{
    public function __construct(
        private UserProviderGateway $userProviderGateway,
        private TaskProviderGateway $taskProviderGateway,
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

        return $this->outputFactory->buildOne($task);
    }
}
