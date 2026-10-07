<?php

declare(strict_types=1);

namespace App\Domain\Validation\Constraint\Training;

use App\Domain\DTO\DataModel\Training\EquipmentDataModel;

/**
 * Two equipments under one name — "Barbell" and "barbell" — could not be told apart in a movement.
 */
final readonly class EquipmentNameAvailableConstraint
{
    public const string NAME_ALREADY_USED = 'equipment_name_already_used';

    /**
     * @param EquipmentDataModel|null     $withSameName the row already carrying that name, ignoring case, if any
     * @param int|null                    $exceptId     the row being renamed, which may keep its own name
     * @param array<string, list<string>> $violations
     *
     * @return array<string, list<string>>
     */
    public static function validate(?EquipmentDataModel $withSameName, ?int $exceptId = null, array $violations = []): array
    {
        if (null === $withSameName) {
            return $violations;
        }

        // Spelled out rather than compared straight: a creation has a null id, and so does an
        // unsaved row, which would otherwise look like a match.
        if (null !== $exceptId && $withSameName->id === $exceptId) {
            return $violations;
        }

        $violations['name'][] = self::NAME_ALREADY_USED;

        return $violations;
    }
}
