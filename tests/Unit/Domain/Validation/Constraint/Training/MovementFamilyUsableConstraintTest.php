<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Constraint\Training;

use App\Domain\DTO\DataModel\Training\MovementFamilyDataModel;
use App\Domain\Validation\Constraint\Training\MovementFamilyUsableConstraint;
use PHPUnit\Framework\TestCase;

final class MovementFamilyUsableConstraintTest extends TestCase
{
    public function testAnActiveFamilyIsUsable(): void
    {
        self::assertSame([], MovementFamilyUsableConstraint::validate($this->family(3, true)));
    }

    public function testAMissingFamilyIsNotFound(): void
    {
        self::assertSame(
            ['movementFamilyId' => [MovementFamilyUsableConstraint::MOVEMENT_FAMILY_NOT_FOUND]],
            MovementFamilyUsableConstraint::validate(null),
        );
    }

    public function testAMissingFamilyIsNotFoundOnAChangeToo(): void
    {
        self::assertSame(
            ['movementFamilyId' => [MovementFamilyUsableConstraint::MOVEMENT_FAMILY_NOT_FOUND]],
            MovementFamilyUsableConstraint::validate(null, 3),
        );
    }

    public function testAnInactiveFamilyTakesNoNewMovement(): void
    {
        self::assertSame(
            ['movementFamilyId' => [MovementFamilyUsableConstraint::MOVEMENT_FAMILY_INACTIVE]],
            MovementFamilyUsableConstraint::validate($this->family(3, false)),
        );
    }

    public function testAnInactiveFamilyTakesNoMovementMovedIn(): void
    {
        self::assertArrayHasKey('movementFamilyId', MovementFamilyUsableConstraint::validate($this->family(3, false), 4));
    }

    public function testStayingInTheInactiveFamilyItSitsInIsNotAMove(): void
    {
        self::assertSame([], MovementFamilyUsableConstraint::validate($this->family(3, false), 3));
    }

    public function testMovingIntoAnActiveFamilyIsAccepted(): void
    {
        self::assertSame([], MovementFamilyUsableConstraint::validate($this->family(3, true), 4));
    }

    private function family(int $id, bool $isActive): MovementFamilyDataModel
    {
        $family = new MovementFamilyDataModel();
        $family->id = $id;
        $family->name = 'Bench press';
        $family->isActive = $isActive;

        return $family;
    }
}
