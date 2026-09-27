<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Constraint\Workout;

use App\Domain\DTO\DataModel\EquipmentDataModel;
use App\Domain\Validation\Constraint\Workout\EquipmentNameAvailableConstraint;
use PHPUnit\Framework\TestCase;

final class EquipmentNameAvailableConstraintTest extends TestCase
{
    public function testAFreeNameIsAccepted(): void
    {
        self::assertSame([], EquipmentNameAvailableConstraint::validate(null));
    }

    public function testANameAnotherRowCarriesIsRefused(): void
    {
        self::assertSame(
            ['name' => [EquipmentNameAvailableConstraint::NAME_ALREADY_USED]],
            EquipmentNameAvailableConstraint::validate($this->row(7)),
        );
    }

    public function testTheRowBeingRenamedMayKeepItsOwnName(): void
    {
        self::assertSame([], EquipmentNameAvailableConstraint::validate($this->row(7), 7));
    }

    public function testARenameOntoAnotherRowIsRefused(): void
    {
        self::assertArrayHasKey('name', EquipmentNameAvailableConstraint::validate($this->row(7), 8));
    }

    private function row(int $id): EquipmentDataModel
    {
        $row = new EquipmentDataModel();
        $row->id = $id;
        $row->name = 'Barbell';

        return $row;
    }
}
