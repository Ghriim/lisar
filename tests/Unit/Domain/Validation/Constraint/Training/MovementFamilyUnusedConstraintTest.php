<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Constraint\Training;

use App\Domain\Validation\Constraint\Training\MovementFamilyUnusedConstraint;
use PHPUnit\Framework\TestCase;

final class MovementFamilyUnusedConstraintTest extends TestCase
{
    public function testAnEmptyFamilyMayGo(): void
    {
        self::assertSame([], MovementFamilyUnusedConstraint::validate(0));
    }

    public function testAFamilyWithASingleMovementMayNot(): void
    {
        self::assertSame(
            ['id' => [MovementFamilyUnusedConstraint::IN_USE]],
            MovementFamilyUnusedConstraint::validate(1),
        );
    }

    public function testItAddsToTheViolationsAlreadyFound(): void
    {
        self::assertSame(
            ['name' => ['name_required'], 'id' => [MovementFamilyUnusedConstraint::IN_USE]],
            MovementFamilyUnusedConstraint::validate(3, ['name' => ['name_required']]),
        );
    }
}
