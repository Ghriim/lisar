<?php

declare(strict_types=1);

namespace App\Domain\Validation\Constraint\Workout;

/**
 * Deleting a muscle a movement targets would take a primary muscle from under it, or quietly thin
 * its secondary ones. Deactivating retires it and leaves the movements as they are.
 */
final readonly class MuscleUnusedConstraint
{
    public const string IN_USE = 'muscle_in_use';

    /**
     * @param int                         $movementCount how many movements, anyone's, target it, as primary or secondary
     * @param array<string, list<string>> $violations
     *
     * @return array<string, list<string>>
     */
    public static function validate(int $movementCount, array $violations = []): array
    {
        if (0 < $movementCount) {
            $violations['id'][] = self::IN_USE;
        }

        return $violations;
    }
}
