<?php

declare(strict_types=1);

namespace App\Domain\Validation\Constraint\Workout;

use App\Domain\DTO\DataModel\MovementFamilyDataModel;

/**
 * A movement goes in a family that exists and is active. An inactive family takes no new movement
 * — neither created in it nor moved into it — but a movement already in one stays there when it is
 * changed otherwise: staying is not moving.
 */
final readonly class MovementFamilyUsableConstraint
{
    public const string MOVEMENT_FAMILY_NOT_FOUND = 'movement_family_not_found';
    public const string MOVEMENT_FAMILY_INACTIVE = 'movement_family_inactive';

    /**
     * @param MovementFamilyDataModel|null $movementFamily  the family asked for, null when none has that id
     * @param int|null                     $currentFamilyId the family the movement sits in today; null on a creation
     * @param array<string, list<string>>  $violations
     *
     * @return array<string, list<string>>
     */
    public static function validate(
        ?MovementFamilyDataModel $movementFamily,
        ?int $currentFamilyId = null,
        array $violations = [],
    ): array {
        if (null === $movementFamily) {
            $violations['movementFamilyId'][] = self::MOVEMENT_FAMILY_NOT_FOUND;

            return $violations;
        }

        if (false === $movementFamily->isActive && (null === $currentFamilyId || $movementFamily->id !== $currentFamilyId)) {
            $violations['movementFamilyId'][] = self::MOVEMENT_FAMILY_INACTIVE;
        }

        return $violations;
    }
}
