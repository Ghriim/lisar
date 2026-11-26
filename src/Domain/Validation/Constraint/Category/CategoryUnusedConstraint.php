<?php

declare(strict_types=1);

namespace App\Domain\Validation\Constraint\Category;

/**
 * Deleting a category that something still points at — a task, a catalogue habit — would either
 * orphan it or silently empty a field someone filled in. They empty it themselves, or keep the
 * category.
 */
final readonly class CategoryUnusedConstraint
{
    public const string CATEGORY_IN_USE = 'category_in_use';

    /**
     * @param int                         $useCount   how many things, every domain together, sit in that category
     * @param array<string, list<string>> $violations
     *
     * @return array<string, list<string>>
     */
    public static function validate(int $useCount, array $violations = []): array
    {
        if (0 < $useCount) {
            $violations['id'][] = self::CATEGORY_IN_USE;
        }

        return $violations;
    }
}
