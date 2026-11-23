<?php

declare(strict_types=1);

namespace App\Domain\Validation\Constraint\Training;

/**
 * Deleting a movement a workout logged would take that workout's sets with it, and one a routine
 * prescribes, that routine's. Deactivating retires it and leaves both as they are.
 */
final readonly class MovementUnusedConstraint
{
    public const string IN_USE = 'movement_in_use';

    /**
     * @param int                         $exerciseCount how many exercises of workouts and routines, anyone's, are of it
     * @param array<string, list<string>> $violations
     *
     * @return array<string, list<string>>
     */
    public static function validate(int $exerciseCount, array $violations = []): array
    {
        if (0 < $exerciseCount) {
            $violations['id'][] = self::IN_USE;
        }

        return $violations;
    }
}
