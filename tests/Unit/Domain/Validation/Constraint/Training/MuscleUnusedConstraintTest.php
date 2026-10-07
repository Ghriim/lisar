<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Constraint\Training;

use App\Domain\Validation\Constraint\Training\MuscleUnusedConstraint;
use PHPUnit\Framework\TestCase;

final class MuscleUnusedConstraintTest extends TestCase
{
    public function testAnUntargetedMuscleMayGo(): void
    {
        self::assertSame([], MuscleUnusedConstraint::validate(0));
    }

    public function testAMuscleASingleMovementTargetsMayNot(): void
    {
        self::assertSame(
            ['id' => [MuscleUnusedConstraint::IN_USE]],
            MuscleUnusedConstraint::validate(1),
        );
    }

    public function testItAddsToTheViolationsAlreadyFound(): void
    {
        self::assertSame(
            ['name' => ['name_required'], 'id' => [MuscleUnusedConstraint::IN_USE]],
            MuscleUnusedConstraint::validate(3, ['name' => ['name_required']]),
        );
    }
}
