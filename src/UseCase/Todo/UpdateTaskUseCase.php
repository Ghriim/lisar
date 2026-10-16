<?php

declare(strict_types=1);

namespace App\UseCase\Todo;

use App\Domain\DTO\DataModel\Todo\TaskDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Todo\UpdateTaskDataInput;
use App\Domain\DTO\Output\Todo\TaskDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\Todo\TaskOutputFactory;
use App\Domain\Gateway\Persister\Todo\TaskPersisterGateway;
use App\Domain\Gateway\Provider\Todo\CategoryProviderGateway;
use App\Domain\Gateway\Provider\Todo\TaskProviderGateway;
use App\Domain\Validation\Validator\Todo\UpdateTaskValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

final readonly class UpdateTaskUseCase implements UseCaseInterface
{
    public function __construct(
        private UpdateTaskValidator $validator,
        private CategoryProviderGateway $categoryProviderGateway,
        private TaskProviderGateway $taskProviderGateway,
        private TaskPersisterGateway $taskPersisterGateway,
        private TaskOutputFactory $outputFactory,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(UserDataModel $currentUser, int $taskId, UpdateTaskDataInput $input): TaskDataOutput
    {
        $task = $this->taskProviderGateway->findOneById($taskId);
        if (null === $task) {
            throw new DataModelNotFoundException(TaskDataModel::class);
        }

        $category = null === $input->categoryId
            ? null
            : $this->categoryProviderGateway->findOneById($input->categoryId);

        $this->validator->validate($currentUser, $task, $input, $category, $currentUser);

        $task->title = $input->title;
        $task->description = $input->description;
        $task->dueDate = $input->getDueDate();
        // Unlike a creation, clearing the priority is meant: the default is not re-applied.
        $task->priority = $input->priority;
        $task->category = $category;

        $this->taskPersisterGateway->update($task);

        return $this->outputFactory->buildOne($task);
    }
}
