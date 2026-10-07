<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Constraint\Training;

use App\Domain\DTO\DataModel\Training\EquipmentDataModel;
use App\Domain\DTO\DataModel\Training\MovementDataModel;
use App\Domain\Validation\Constraint\Training\MovementEquipmentsConstraint;
use PHPUnit\Framework\TestCase;

final class MovementEquipmentsConstraintTest extends TestCase
{
    public function testActiveEquipmentsAreAccepted(): void
    {
        self::assertSame([], MovementEquipmentsConstraint::validate([1, 2], [$this->equipment(1), $this->equipment(2)]));
    }

    public function testNoEquipmentAtAllIsABodyweightMovement(): void
    {
        self::assertSame([], MovementEquipmentsConstraint::validate([], []));
    }

    public function testAMissingEquipmentIsNotFound(): void
    {
        self::assertSame(
            ['equipmentIds' => [MovementEquipmentsConstraint::EQUIPMENT_NOT_FOUND]],
            MovementEquipmentsConstraint::validate([1, 2], [$this->equipment(1)]),
        );
    }

    public function testAnInactiveEquipmentIsRefused(): void
    {
        self::assertSame(
            ['equipmentIds' => [MovementEquipmentsConstraint::EQUIPMENT_INACTIVE]],
            MovementEquipmentsConstraint::validate([1], [$this->equipment(1, false)]),
        );
    }

    public function testSeveralInactiveEquipmentsAreReportedOnce(): void
    {
        self::assertSame(
            ['equipmentIds' => [MovementEquipmentsConstraint::EQUIPMENT_INACTIVE]],
            MovementEquipmentsConstraint::validate([1, 2], [$this->equipment(1, false), $this->equipment(2, false)]),
        );
    }

    public function testItAccumulatesEveryViolation(): void
    {
        self::assertSame(
            [
                'name' => ['name_required'],
                'equipmentIds' => [
                    MovementEquipmentsConstraint::EQUIPMENT_NOT_FOUND,
                    MovementEquipmentsConstraint::EQUIPMENT_INACTIVE,
                ],
            ],
            MovementEquipmentsConstraint::validate([1, 2], [$this->equipment(1, false)], null, ['name' => ['name_required']]),
        );
    }

    public function testAnEquipmentRetiredSinceMayStay(): void
    {
        $current = $this->movement([$this->equipment(1, false)]);

        self::assertSame(
            [],
            MovementEquipmentsConstraint::validate([1, 2], [$this->equipment(1, false), $this->equipment(2)], $current),
        );
    }

    public function testARetiredEquipmentTheMovementDoesNotHaveIsRefused(): void
    {
        $current = $this->movement([$this->equipment(1)]);

        self::assertSame(
            ['equipmentIds' => [MovementEquipmentsConstraint::EQUIPMENT_INACTIVE]],
            MovementEquipmentsConstraint::validate([1, 2], [$this->equipment(1), $this->equipment(2, false)], $current),
        );
    }

    private function equipment(int $id, bool $isActive = true): EquipmentDataModel
    {
        $equipment = new EquipmentDataModel();
        $equipment->id = $id;
        $equipment->name = 'Equipment '.$id;
        $equipment->isActive = $isActive;

        return $equipment;
    }

    /**
     * @param list<EquipmentDataModel> $equipments
     */
    private function movement(array $equipments): MovementDataModel
    {
        $movement = new MovementDataModel();
        $movement->id = 10;
        $movement->name = 'Bench press (barbell)';
        foreach ($equipments as $equipment) {
            $movement->equipments->add($equipment);
        }

        return $movement;
    }
}
