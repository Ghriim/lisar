<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Constraint\Training;

use App\Domain\DTO\DataModel\Training\MuscleGroupDataModel;
use App\Domain\Validation\Constraint\Training\MuscleGroupUsableConstraint;
use PHPUnit\Framework\TestCase;

final class MuscleGroupUsableConstraintTest extends TestCase
{
    public function testAnActiveGroupIsUsable(): void
    {
        self::assertSame([], MuscleGroupUsableConstraint::validate($this->group(3, true)));
    }

    public function testAMissingGroupIsNotFound(): void
    {
        self::assertSame(
            ['muscleGroupId' => [MuscleGroupUsableConstraint::MUSCLE_GROUP_NOT_FOUND]],
            MuscleGroupUsableConstraint::validate(null),
        );
    }

    public function testAnInactiveGroupTakesNoNewMuscle(): void
    {
        self::assertSame(
            ['muscleGroupId' => [MuscleGroupUsableConstraint::MUSCLE_GROUP_INACTIVE]],
            MuscleGroupUsableConstraint::validate($this->group(3, false)),
        );
    }

    public function testAnInactiveGroupTakesNoMuscleMovedIn(): void
    {
        self::assertArrayHasKey('muscleGroupId', MuscleGroupUsableConstraint::validate($this->group(3, false), 4));
    }

    public function testStayingInTheInactiveGroupItSitsInIsNotAMove(): void
    {
        self::assertSame([], MuscleGroupUsableConstraint::validate($this->group(3, false), 3));
    }

    private function group(int $id, bool $isActive): MuscleGroupDataModel
    {
        $group = new MuscleGroupDataModel();
        $group->id = $id;
        $group->name = 'Chest';
        $group->isActive = $isActive;

        return $group;
    }
}
