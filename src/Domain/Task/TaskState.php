<?php

declare(strict_types=1);

namespace App\Domain\Task;

use App\Domain\DTO\DataModel\TaskDataModel;
use App\Domain\Registry\Task\TaskStateRegistry;

use function count;

/**
 * Reads a task's state off its own progress. Nothing here is stored: the only thing a person
 * sets is "done", and the two middle states exist only for a task that has subtasks.
 */
final readonly class TaskState
{
    public function resolve(TaskDataModel $task): string
    {
        if (true === $task->isDone()) {
            return TaskStateRegistry::DONE;
        }

        // A subtask cannot itself have subtasks, so the two derived states never apply to it —
        // and asking its always-empty collection would fire a query for nothing.
        if (true === $task->isSubtask()) {
            return TaskStateRegistry::TO_DO;
        }

        $subtaskCount = $task->subtasks->count();

        if (0 === $subtaskCount) {
            // A task without subtasks cannot be "started": it goes straight to done.
            return TaskStateRegistry::TO_DO;
        }

        $openCount = count($task->getOpenSubtasks());

        if ($subtaskCount === $openCount) {
            return TaskStateRegistry::TO_DO;
        }

        if (0 === $openCount) {
            return TaskStateRegistry::READY_TO_CLOSE;
        }

        return TaskStateRegistry::IN_PROGRESS;
    }
}
