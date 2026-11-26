<?php

declare(strict_types=1);

namespace App\Domain\Validation\Constraint\Todo;

use App\Domain\DTO\DataModel\Todo\TaskDataModel;

/**
 * A star is a root task's: a subtask is reached through its parent, and is never put forward on
 * its own.
 */
final readonly class TaskFavoritableConstraint
{
    public const string SUBTASK_NOT_FAVORITABLE = 'subtask_not_favoritable';

    /**
     * @param array<string, list<string>> $violations
     *
     * @return array<string, list<string>>
     */
    public static function validate(TaskDataModel $task, array $violations = []): array
    {
        if (null !== $task->parent) {
            $violations['id'][] = self::SUBTASK_NOT_FAVORITABLE;
        }

        return $violations;
    }
}
