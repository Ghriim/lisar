<?php

declare(strict_types=1);

namespace App\Domain\Factory\OutputFactory;

use App\Domain\DTO\DataModel\TaskDataModel;
use App\Domain\DTO\Output\Task\TaskDataOutput;
use App\Domain\Task\TaskState;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final readonly class TaskOutputFactory
{
    public function __construct(
        private ObjectMapperInterface $mapper,
        private TaskState $taskState,
        private PriorityOutputFactory $priorityOutputFactory,
        private CategoryOutputFactory $categoryOutputFactory,
    ) {
    }

    /**
     * @param TaskDataModel[] $tasks
     *
     * @return list<TaskDataOutput>
     */
    public function buildMany(array $tasks): array
    {
        $outputs = [];
        foreach ($tasks as $task) {
            $outputs[] = $this->buildOne($task);
        }

        return $outputs;
    }

    public function buildOne(TaskDataModel $task): TaskDataOutput
    {
        $output = $this->mapper->map($task, TaskDataOutput::class);

        $output->state = $this->taskState->resolve($task);
        $output->parentId = $task->parent?->id;

        if (null !== $task->priority) {
            $output->priority = $this->priorityOutputFactory->buildOne($task->priority);
        }

        if (null !== $task->category) {
            $output->category = $this->categoryOutputFactory->buildOne($task->category);
        }

        $labels = [];
        foreach ($task->tags as $tag) {
            $labels[] = $tag->label;
        }
        sort($labels);
        $output->tags = $labels;

        // The recursion stops by itself: a subtask never has subtasks, and asking for them
        // would be a query per row.
        if (false === $task->isSubtask()) {
            $output->subtasks = $this->buildMany($task->subtasks->toArray());
        }

        return $output;
    }
}
