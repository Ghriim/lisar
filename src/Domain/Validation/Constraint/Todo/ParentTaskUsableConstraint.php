<?php

declare(strict_types=1);

namespace App\Domain\Validation\Constraint\Todo;

use App\Domain\DTO\DataModel\Todo\TaskDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;

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
    public static function validate(?int $requestedId, ?TaskDataModel $parent, UserDataModel $currentUser, array $violations = []): array
    {
        if (null === $requestedId) {
            return $violations;
        }

        // Someone else's task is answered like one that does not exist: its id says nothing.
        if (null === $parent || $currentUser->id !== $parent->owner->id) {
            $violations['parentId'][] = self::PARENT_TASK_NOT_FOUND;

            return $violations;
        }

        if (true === $parent->isSubtask()) {
            $violations['parentId'][] = self::PARENT_TASK_IS_A_SUBTASK;
        }

        return $violations;
    }
}
