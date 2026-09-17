<?php

declare(strict_types=1);

namespace App\Domain\Validation\Constraint\Task;

use App\Domain\DTO\DataModel\TaskDataModel;

/**
 * A parent cannot be closed while one of its subtasks is still open, and closing it never closes
 * them: the person ticks the last one off, then closes the parent.
 */
final readonly class TaskClosableConstraint
{
    public const string TASK_HAS_OPEN_SUBTASKS = 'task_has_open_subtasks';

    /**
     * @param array<string, list<string>> $violations
     *
     * @return array<string, list<string>>
     */
    public static function validate(TaskDataModel $task, array $violations = []): array
    {
        if ([] !== $task->getOpenSubtasks()) {
            $violations['id'][] = self::TASK_HAS_OPEN_SUBTASKS;
        }

        return $violations;
    }
}
