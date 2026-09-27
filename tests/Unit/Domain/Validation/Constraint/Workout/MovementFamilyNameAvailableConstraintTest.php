<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Constraint\Workout;

use App\Domain\DTO\DataModel\MovementFamilyDataModel;
use App\Domain\Validation\Constraint\Workout\MovementFamilyNameAvailableConstraint;
use PHPUnit\Framework\TestCase;

final class MovementFamilyNameAvailableConstraintTest extends TestCase
{
    public function testAFreeNameIsAccepted(): void
    {
        self::assertSame([], MovementFamilyNameAvailableConstraint::validate(null));
    }

    public function testANameAnotherRowCarriesIsRefused(): void
    {
        self::assertSame(
            ['name' => [MovementFamilyNameAvailableConstraint::NAME_ALREADY_USED]],
            MovementFamilyNameAvailableConstraint::validate($this->row(7)),
        );
    }

    public function testTheRowBeingRenamedMayKeepItsOwnName(): void
    {
        self::assertSame([], MovementFamilyNameAvailableConstraint::validate($this->row(7), 7));
    }

    public function testARenameOntoAnotherRowIsRefused(): void
    {
        self::assertArrayHasKey('name', MovementFamilyNameAvailableConstraint::validate($this->row(7), 8));
    }

    public function testAnUnsavedRowIsNotMistakenForTheOneBeingCreated(): void
    {
        self::assertArrayHasKey('name', MovementFamilyNameAvailableConstraint::validate(new MovementFamilyDataModel()));
    }

    private function row(int $id): MovementFamilyDataModel
    {
        $row = new MovementFamilyDataModel();
        $row->id = $id;
        $row->name = 'Push-ups';

        return $row;
    }
}
