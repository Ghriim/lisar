<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Constraint\Training;

use App\Domain\DTO\DataModel\Training\MovementDataModel;
use App\Domain\Validation\Constraint\Training\MovementNameAvailableConstraint;
use PHPUnit\Framework\TestCase;

final class MovementNameAvailableConstraintTest extends TestCase
{
    public function testAFreeNameIsAccepted(): void
    {
        self::assertSame([], MovementNameAvailableConstraint::validate(null));
    }

    public function testANameAnotherRowCarriesIsRefused(): void
    {
        self::assertSame(
            ['name' => [MovementNameAvailableConstraint::NAME_ALREADY_USED]],
            MovementNameAvailableConstraint::validate($this->row(7)),
        );
    }

    public function testTheRowBeingRenamedMayKeepItsOwnName(): void
    {
        self::assertSame([], MovementNameAvailableConstraint::validate($this->row(7), 7));
    }

    public function testARenameOntoAnotherRowIsRefused(): void
    {
        self::assertArrayHasKey('name', MovementNameAvailableConstraint::validate($this->row(7), 8));
    }

    public function testAnUnsavedRowIsNotMistakenForTheOneBeingCreated(): void
    {
        self::assertArrayHasKey('name', MovementNameAvailableConstraint::validate(new MovementDataModel()));
    }

    private function row(int $id): MovementDataModel
    {
        $row = new MovementDataModel();
        $row->id = $id;
        $row->name = 'Bench press (barbell)';

        return $row;
    }
}
