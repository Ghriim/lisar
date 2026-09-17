<?php

declare(strict_types=1);

namespace App\Domain\Validation\Constraint\Task;

use App\Domain\DTO\DataModel\PriorityDataModel;

/**
 * Two reasons a priority stays: tasks point at it, or it is the one applied to a task created
 * without one. The second is why the set can never be emptied by accident.
 */
final readonly class PriorityDeletableConstraint
{
    public const string PRIORITY_IN_USE = 'priority_in_use';
    public const string PRIORITY_IS_THE_DEFAULT = 'priority_is_the_default';

    /**
     * @param int                         $taskCount  how many tasks carry that priority
     * @param array<string, list<string>> $violations
     *
     * @return array<string, list<string>>
     */
    public static function validate(
        PriorityDataModel $priority,
        int $taskCount,
        array $violations = [],
    ): array {
        if (0 < $taskCount) {
            $violations['id'][] = self::PRIORITY_IN_USE;
        }

        if (true === $priority->isDefault) {
            $violations['id'][] = self::PRIORITY_IS_THE_DEFAULT;
        }

        return $violations;
    }
}
