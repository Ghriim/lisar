<?php

declare(strict_types=1);

namespace App\UseCase\Todo;

use App\Domain\DTO\DataModel\Todo\TaskDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Todo\CreateTaskDataInput;
use App\Domain\DTO\Output\Todo\TaskDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\Todo\TaskOutputFactory;
use App\Domain\Gateway\Persister\Todo\TaskPersisterGateway;
use App\Domain\Gateway\Provider\Category\CategoryProviderGateway;
use App\Domain\Gateway\Provider\Todo\TaskProviderGateway;
use App\Domain\Gateway\Provider\Todo\TodoProviderGateway;
use App\Domain\Registry\Todo\TaskPriorityRegistry;
use App\Domain\Validation\Validator\Todo\CreateTaskValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;
use LogicException;

final readonly class CreateTaskUseCase implements UseCaseInterface
{
    public function __construct(
        private CreateTaskValidator $validator,
        private CategoryProviderGateway $categoryProviderGateway,
        private TaskProviderGateway $taskProviderGateway,
        private TodoProviderGateway $todoProviderGateway,
        private TaskPersisterGateway $taskPersisterGateway,
        private TaskOutputFactory $outputFactory,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(UserDataModel $currentUser, CreateTaskDataInput $input): TaskDataOutput
    {
        $category = null === $input->categoryId
            ? null
            : $this->categoryProviderGateway->findOneById($input->categoryId);

        // Whoever it belongs to: someone else's task is refused by the validator, as one not found.
        $parent = null === $input->parentId
            ? null
            : $this->taskProviderGateway->findOneById($input->parentId);

        // A subtask sits in its parent's TODO, whatever was named; a task in the one named —
        // whoever's, refused by the validator — or else in the account's default.
        $todo = match (true) {
            null !== $parent => $parent->todo,
            null !== $input->todoId => $this->todoProviderGateway->findOneById($input->todoId),
            default => $this->todoProviderGateway->findDefaultForOwner($currentUser),
        };

        $this->validator->validate($currentUser, $input, $category, $parent, $currentUser, $todo);

        $task = new TaskDataModel();
        $task->owner = $currentUser;
        $task->todo = $todo ?? throw new LogicException('The validator lets no task through without its TODO.');
        $task->title = $input->title;
        $task->description = $input->description;
        $task->dueDate = $input->getDueDate();
        // The default applies when the caller names no priority.
        $task->priority = $input->priority ?? TaskPriorityRegistry::DEFAULT;
        $task->category = $category;
        $task->parent = $parent;

        $this->taskPersisterGateway->create($task);

        // Keep both sides of the association in step: whoever reads the parent in this same
        // request must see its new subtask, state included.
        $parent?->subtasks->add($task);

        return $this->outputFactory->buildOne($task);
    }
}
