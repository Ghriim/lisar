<?php

declare(strict_types=1);

namespace App\UseCase\Task;

use App\Domain\DTO\DataModel\TagDataModel;
use App\Domain\DTO\DataModel\TaskDataModel;
use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\DTO\Input\Task\UpdateTaskDataInput;
use App\Domain\DTO\Output\Task\TaskDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\DataModelFactory\TagDataModelFactory;
use App\Domain\Factory\OutputFactory\TaskOutputFactory;
use App\Domain\Gateway\Persister\TagPersisterGateway;
use App\Domain\Gateway\Persister\TaskPersisterGateway;
use App\Domain\Gateway\Provider\CategoryProviderGateway;
use App\Domain\Gateway\Provider\PriorityProviderGateway;
use App\Domain\Gateway\Provider\TagProviderGateway;
use App\Domain\Gateway\Provider\TaskProviderGateway;
use App\Domain\Gateway\Provider\UserProviderGateway;
use App\Domain\Validation\Validator\Task\UpdateTaskValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

final readonly class UpdateTaskUseCase implements UseCaseInterface
{
    public function __construct(
        private UpdateTaskValidator $validator,
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
    public function execute(int $ownerId, int $taskId, UpdateTaskDataInput $input): TaskDataOutput
    {
        $owner = $this->userProviderGateway->findOneById($ownerId);
        if (null === $owner) {
            throw new DataModelNotFoundException(UserDataModel::class);
        }

        $task = $this->taskProviderGateway->findOneByIdForOwner($taskId, $owner);
        if (null === $task) {
            throw new DataModelNotFoundException(TaskDataModel::class);
        }

        $priority = null === $input->priorityId
            ? null
            : $this->priorityProviderGateway->findOneById($input->priorityId);

        $category = null === $input->categoryId
            ? null
            : $this->categoryProviderGateway->findOneById($input->categoryId);

        $this->validator->validate($input, $priority, $category, $owner);

        $task->title = $input->title;
        $task->description = $input->description;
        $task->dueDate = $input->getDueDate();
        // Unlike a creation, clearing the priority is meant: the default is not re-applied.
        $task->priority = $priority;
        $task->category = $category;

        // The tags are the whole set: what the caller left out is taken off the task. The tag
        // itself stays on the account, which is what lets it be offered again.
        $task->tags->clear();
        foreach ($this->resolveTags($input->getTagLabels(), $owner) as $tag) {
            $task->tags->add($tag);
        }

        $this->taskPersisterGateway->update($task);

        return $this->outputFactory->buildOne($task);
    }

    /**
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
