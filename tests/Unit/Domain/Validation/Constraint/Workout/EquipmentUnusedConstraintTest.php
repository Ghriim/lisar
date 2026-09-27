<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Constraint\Workout;

use App\Domain\Validation\Constraint\Workout\EquipmentUnusedConstraint;
use PHPUnit\Framework\TestCase;

final class EquipmentUnusedConstraintTest extends TestCase
{
    public function testAnUnusedEquipmentMayGo(): void
    {
        self::assertSame([], EquipmentUnusedConstraint::validate(0));
    }

    public function testAnEquipmentASingleMovementIsDoneWithMayNot(): void
    {
        self::assertSame(
            ['id' => [EquipmentUnusedConstraint::IN_USE]],
            EquipmentUnusedConstraint::validate(1),
        );
    }

    public function testItAddsToTheViolationsAlreadyFound(): void
    {
        self::assertSame(
            ['name' => ['name_required'], 'id' => [EquipmentUnusedConstraint::IN_USE]],
            EquipmentUnusedConstraint::validate(3, ['name' => ['name_required']]),
        );
    }
}
