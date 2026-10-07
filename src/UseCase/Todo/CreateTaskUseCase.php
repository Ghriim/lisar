<?php

declare(strict_types=1);

namespace App\UseCase\Todo;

use App\Domain\DTO\DataModel\Todo\TagDataModel;
use App\Domain\DTO\DataModel\Todo\TaskDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Todo\CreateTaskDataInput;
use App\Domain\DTO\Output\Todo\TaskDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\DataModelFactory\Todo\TagDataModelFactory;
use App\Domain\Factory\OutputFactory\Todo\TaskOutputFactory;
use App\Domain\Gateway\Persister\Todo\TagPersisterGateway;
use App\Domain\Gateway\Persister\Todo\TaskPersisterGateway;
use App\Domain\Gateway\Provider\Todo\CategoryProviderGateway;
use App\Domain\Gateway\Provider\Todo\PriorityProviderGateway;
use App\Domain\Gateway\Provider\Todo\TagProviderGateway;
use App\Domain\Gateway\Provider\Todo\TaskProviderGateway;
use App\Domain\Gateway\Provider\User\UserProviderGateway;
use App\Domain\Validation\Validator\Todo\CreateTaskValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

final readonly class CreateTaskUseCase implements UseCaseInterface
{
    public function __construct(
        private CreateTaskValidator $validator,
        private UserProviderGateway $userProviderGateway,
        private PriorityProviderGateway $priorityProviderGateway,
        private CategoryProviderGateway $categoryProviderGateway,
        private TaskProviderGateway $taskProviderGateway,
        private TagProviderGateway $tagProviderGateway,
        private TaskPersisterGateway $taskPersisterGateway,
        private TagPersisterGateway $tagPersisterGateway,
        private TagDataModelFactory $tagDataModelFactory,
        private TaskOutputFactory $outputFactory,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(int $ownerId, CreateTaskDataInput $input): TaskDataOutput
    {
        $owner = $this->userProviderGateway->findOneById($ownerId);
        if (null === $owner) {
            throw new DataModelNotFoundException(UserDataModel::class);
        }

        $priority = null === $input->priorityId
            ? null
            : $this->priorityProviderGateway->findOneById($input->priorityId);

        $category = null === $input->categoryId
            ? null
            : $this->categoryProviderGateway->findOneById($input->categoryId);

        // Scoped to the owner: someone else's task is not a parent this account can name.
        $parent = null === $input->parentId
            ? null
            : $this->taskProviderGateway->findOneByIdForOwner($input->parentId, $owner);

        $this->validator->validate($input, $priority, $category, $parent, $owner);

        $task = new TaskDataModel();
        $task->owner = $owner;
        $task->title = $input->title;
        $task->description = $input->description;
        $task->dueDate = $input->getDueDate();
        // The back-office default applies when the caller names no priority.
        $task->priority = $priority ?? $this->priorityProviderGateway->findOneDefault();
        $task->category = $category;
        $task->parent = $parent;

        foreach ($this->resolveTags($input->getTagLabels(), $owner) as $tag) {
            $task->tags->add($tag);
        }

        $this->taskPersisterGateway->create($task);

        // Keep both sides of the association in step: whoever reads the parent in this same
        // request must see its new subtask, state included.
        $parent?->subtasks->add($task);

        return $this->outputFactory->buildOne($task);
    }

    /**
     * The account's tags for those labels, creating the ones it does not have yet.
     *
     * @param list<string> $labels
     *
     * @return list<TagDataModel>
     */
    private function resolveTags(array $labels, UserDataModel $owner): array
    {
        if ([] === $labels) {
            return [];
        }

        $existingTags = $this->tagProviderGateway->findAllByLabelsForOwner($owner, $labels);
        $newTags = $this->tagDataModelFactory->buildMany($labels, $owner, $existingTags);

        if ([] !== $newTags) {
            $this->tagPersisterGateway->createMany($newTags);
        }

        return [...$existingTags, ...$newTags];
    }
}
