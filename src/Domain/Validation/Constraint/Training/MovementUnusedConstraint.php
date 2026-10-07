<?php

declare(strict_types=1);

namespace App\Domain\Validation\Constraint\Training;

/**
 * Deleting a movement a workout logged would take that workout's sets with it. Deactivating
 * retires it and leaves the history as it is.
 */
final readonly class MovementUnusedConstraint
{
    public const string IN_USE = 'movement_in_use';

    /**
     * @param int                         $exerciseCount how many logged exercises, anyone's, are of it
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
