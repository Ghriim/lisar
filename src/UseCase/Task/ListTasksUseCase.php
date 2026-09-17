<?php

declare(strict_types=1);

namespace App\UseCase\Task;

use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\DTO\Input\Task\ListTasksDataInput;
use App\Domain\DTO\Output\Task\TaskDataOutput;
use App\Domain\Factory\OutputFactory\TaskOutputFactory;
use App\Domain\Gateway\Provider\TaskProviderGateway;
use App\Domain\Gateway\Provider\UserProviderGateway;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * The account's own list: root tasks with their subtasks nested, sorted by category then by
 * priority. Not paginated — a personal todo list is small, and the sort is meaningless on a
 * page of it.
 */
final readonly class ListTasksUseCase implements UseCaseInterface
{
    public function __construct(
        private UserProviderGateway $userProviderGateway,
        private TaskProviderGateway $taskProviderGateway,
        private TaskOutputFactory $outputFactory,
    ) {
    }

    /**
     * @return list<TaskDataOutput>
     *
     * @throws DataModelNotFoundException
     */
    public function execute(int $ownerId, ListTasksDataInput $input): array
    {
        $owner = $this->userProviderGateway->findOneById($ownerId);
        if (null === $owner) {
            throw new DataModelNotFoundException(UserDataModel::class);
        }

        return $this->outputFactory->buildMany(
            $this->taskProviderGateway->findAllForOwnerList($owner, $input->isDone),
        );
    }
}
