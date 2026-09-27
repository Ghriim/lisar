<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Constraint\Workout;

use App\Domain\Validation\Constraint\Workout\MuscleGroupUnusedConstraint;
use PHPUnit\Framework\TestCase;

final class MuscleGroupUnusedConstraintTest extends TestCase
{
    public function testAnEmptyGroupMayGo(): void
    {
        self::assertSame([], MuscleGroupUnusedConstraint::validate(0));
    }

    public function testAGroupWithASingleMuscleMayNot(): void
    {
        self::assertSame(
            ['id' => [MuscleGroupUnusedConstraint::MUSCLE_GROUP_IN_USE]],
            MuscleGroupUnusedConstraint::validate(1),
        );
    }
}
