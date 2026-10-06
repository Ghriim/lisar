<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Constraint\Workout;

use App\Domain\DTO\DataModel\SetTypeDataModel;
use App\Domain\Validation\Constraint\Workout\WorkoutSetTypeUsableConstraint;
use PHPUnit\Framework\TestCase;

final class WorkoutSetTypeUsableConstraintTest extends TestCase
{
    public function testNoSetTypeIsAnOrdinarySet(): void
    {
        self::assertSame([], WorkoutSetTypeUsableConstraint::validate(null, null, null));
    }

    public function testAnActiveSetTypeIsAccepted(): void
    {
        self::assertSame([], WorkoutSetTypeUsableConstraint::validate(1, $this->setType(1, true), null));
    }

    public function testAnUnknownSetTypeIsRefused(): void
    {
        self::assertSame(['setTypeId' => [WorkoutSetTypeUsableConstraint::UNKNOWN]], WorkoutSetTypeUsableConstraint::validate(9, null, null));
    }

    public function testARetiredSetTypeIsRefusedOnANewSet(): void
    {
        self::assertSame(['setTypeId' => [WorkoutSetTypeUsableConstraint::INACTIVE]], WorkoutSetTypeUsableConstraint::validate(1, $this->setType(1, false), null));
    }

    public function testARetiredSetTypeMayStayOnTheSetThatCarriesIt(): void
    {
        self::assertSame([], WorkoutSetTypeUsableConstraint::validate(1, $this->setType(1, false), $this->setType(1, false)));
    }

    public function testARetiredSetTypeIsRefusedInPlaceOfAnother(): void
    {
        self::assertArrayHasKey('setTypeId', WorkoutSetTypeUsableConstraint::validate(1, $this->setType(1, false), $this->setType(2, true)));
    }

    private function setType(int $id, bool $isActive): SetTypeDataModel
    {
        $setType = new SetTypeDataModel();
        $setType->id = $id;
        $setType->isActive = $isActive;

        return $setType;
    }
}
