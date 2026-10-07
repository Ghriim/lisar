<?php

declare(strict_types=1);

namespace App\Domain\Validation\Constraint\Training;

/**
 * Deleting an equipment a movement is done with would change what that movement asks for
 * without anyone deciding it. Deactivating retires it and leaves the movements as they are.
 */
final readonly class EquipmentUnusedConstraint
{
    public const string IN_USE = 'equipment_in_use';

    /**
     * @param int                         $movementCount how many movements, anyone's, are done with it
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
