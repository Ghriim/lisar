<?php

declare(strict_types=1);

namespace App\UseCase\Todo;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Todo\ListTasksDataInput;
use App\Domain\DTO\Output\Todo\TaskDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\Todo\TaskOutputFactory;
use App\Domain\Gateway\Provider\Todo\TaskProviderGateway;
use App\Domain\Gateway\Provider\Todo\TodoProviderGateway;
use App\Domain\Validation\Validator\Todo\ListTasksValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;
use LogicException;

/**
 * One of the account's TODOs — its default when none is named, never an archived one: root tasks
 * with their subtasks nested, sorted by category then by priority. Not paginated — a personal
 * todo list is small, and the sort is meaningless on a page of it.
 */
final readonly class ListTasksUseCase implements UseCaseInterface
{
    public function __construct(
        private ListTasksValidator $validator,
        private TaskProviderGateway $taskProviderGateway,
        private TodoProviderGateway $todoProviderGateway,
        private TaskOutputFactory $outputFactory,
    ) {
    }

    /**
     * @return list<TaskDataOutput>
     *
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(UserDataModel $currentUser, ListTasksDataInput $input): array
    {
        $todo = null === $input->todoId
            ? $this->todoProviderGateway->findDefaultForOwner($currentUser)
            : $this->todoProviderGateway->findOneById($input->todoId);

        $this->validator->validate($currentUser, $input, $todo);

        return $this->outputFactory->buildMany(
            $this->taskProviderGateway->findAllForOwnerList(
                $currentUser,
                $todo ?? throw new LogicException('The validator lets no list through without its TODO.'),
                $input->isDone,
            ),
        );
    }
}
