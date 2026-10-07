<?php

declare(strict_types=1);

namespace App\Domain\Validation\Constraint\Training;

/**
 * Deleting a group that still holds muscles would leave them in none, and a muscle is always in
 * one. They are moved or deleted first — or the group is deactivated instead.
 */
final readonly class MuscleGroupUnusedConstraint
{
    public const string MUSCLE_GROUP_IN_USE = 'muscle_group_in_use';

    /**
     * @param int                         $muscleCount how many muscles sit in that group, active or not
     * @param array<string, list<string>> $violations
     *
     * @return array<string, list<string>>
     */
    public static function validate(int $muscleCount, array $violations = []): array
    {
        if (0 < $muscleCount) {
            $violations['id'][] = self::MUSCLE_GROUP_IN_USE;
        }

        return $violations;
    }
}
