<?php

declare(strict_types=1);

namespace App\Domain\Validation\Constraint\Task;

use App\Domain\DTO\DataModel\TaskDataModel;

/**
 * There is one level of subtasks and one only, so a subtask cannot be given subtasks of its own.
 */
final readonly class ParentTaskUsableConstraint
{
    public const string PARENT_TASK_NOT_FOUND = 'parent_task_not_found';
    public const string PARENT_TASK_IS_A_SUBTASK = 'parent_task_is_a_subtask';

    /**
     * @param int|null                    $requestedId the id the caller asked for, if any
     * @param array<string, list<string>> $violations
     *
     * @return array<string, list<string>>
     */
    public static function validate(?int $requestedId, ?TaskDataModel $parent, array $violations = []): array
    {
        if (null === $requestedId) {
            return $violations;
        }

        if (null === $parent) {
            $violations['parentId'][] = self::PARENT_TASK_NOT_FOUND;

            return $violations;
        }

        if (true === $parent->isSubtask()) {
            $violations['parentId'][] = self::PARENT_TASK_IS_A_SUBTASK;
        }

        return $violations;
    }
}
