<?php

declare(strict_types=1);

namespace App\Domain\Validation\Constraint\Workout;

/**
 * Every movement sits in a family, so a family is deleted only once it is empty. To retire one that
 * still holds movements, deactivate it.
 */
final readonly class MovementFamilyUnusedConstraint
{
    public const string IN_USE = 'movement_family_in_use';

    /**
     * @param int                         $movementCount how many movements, anyone's, sit in it
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
