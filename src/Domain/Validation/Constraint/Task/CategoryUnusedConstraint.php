<?php

declare(strict_types=1);

namespace App\Domain\Validation\Constraint\Task;

/**
 * Deleting a category that tasks still point at would either orphan them or silently empty a
 * field the person filled in. They empty it themselves, or keep the category.
 */
final readonly class CategoryUnusedConstraint
{
    public const string CATEGORY_IN_USE = 'category_in_use';

    /**
     * @param int                         $taskCount  how many tasks sit in that category
     * @param array<string, list<string>> $violations
     *
     * @return array<string, list<string>>
     */
    public static function validate(int $taskCount, array $violations = []): array
    {
        if (0 < $taskCount) {
            $violations['id'][] = self::CATEGORY_IN_USE;
        }

        return $violations;
    }
}
