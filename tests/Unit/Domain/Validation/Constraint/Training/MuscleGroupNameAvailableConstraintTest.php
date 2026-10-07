<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Constraint\Training;

use App\Domain\DTO\DataModel\Training\MuscleGroupDataModel;
use App\Domain\Validation\Constraint\Training\MuscleGroupNameAvailableConstraint;
use PHPUnit\Framework\TestCase;

final class MuscleGroupNameAvailableConstraintTest extends TestCase
{
    public function testAFreeNameIsAccepted(): void
    {
        self::assertSame([], MuscleGroupNameAvailableConstraint::validate(null));
    }

    public function testANameAnotherRowCarriesIsRefused(): void
    {
        self::assertSame(
            ['name' => [MuscleGroupNameAvailableConstraint::NAME_ALREADY_USED]],
            MuscleGroupNameAvailableConstraint::validate($this->row(7)),
        );
    }

    public function testTheRowBeingRenamedMayKeepItsOwnName(): void
    {
        self::assertSame([], MuscleGroupNameAvailableConstraint::validate($this->row(7), 7));
    }

    public function testARenameOntoAnotherRowIsRefused(): void
    {
        self::assertArrayHasKey('name', MuscleGroupNameAvailableConstraint::validate($this->row(7), 8));
    }

    private function row(int $id): MuscleGroupDataModel
    {
        $row = new MuscleGroupDataModel();
        $row->id = $id;
        $row->name = 'Chest';

        return $row;
    }
}
