<?php

declare(strict_types=1);

namespace App\Domain\Validation\Constraint\Workout;

use App\Domain\DTO\DataModel\EquipmentDataModel;
use App\Domain\DTO\DataModel\MovementDataModel;

use function count;
use function in_array;

/**
 * What a movement is done with: equipments that all exist and are active. None at all is allowed —
 * that is a bodyweight movement. As with muscles, an equipment retired since it was put on the
 * movement stays there when the movement is changed otherwise.
 */
final readonly class MovementEquipmentsConstraint
{
    public const string EQUIPMENT_NOT_FOUND = 'equipment_not_found';
    public const string EQUIPMENT_INACTIVE = 'equipment_inactive';

    /**
     * @param list<int>                   $equipmentIds the ids asked for, de-duplicated
     * @param list<EquipmentDataModel>    $equipments   the equipments found among them
     * @param MovementDataModel|null      $current      the movement being changed; null on a creation
     * @param array<string, list<string>> $violations
     *
     * @return array<string, list<string>>
     */
    public static function validate(
        array $equipmentIds,
        array $equipments,
        ?MovementDataModel $current = null,
        array $violations = [],
    ): array {
        if (count($equipments) < count($equipmentIds)) {
            $violations['equipmentIds'][] = self::EQUIPMENT_NOT_FOUND;
        }

        $held = [];
        if (null !== $current) {
            foreach ($current->equipments as $equipment) {
                $held[] = $equipment->id;
            }
        }

        foreach ($equipments as $equipment) {
            if (false === $equipment->isActive && false === in_array($equipment->id, $held, true)) {
                $violations['equipmentIds'][] = self::EQUIPMENT_INACTIVE;
                break;
            }
        }

        return $violations;
    }
}
