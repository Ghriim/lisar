<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Constraint\Workout;

use App\Domain\Validation\Constraint\Workout\MovementUnusedConstraint;
use PHPUnit\Framework\TestCase;

final class MovementUnusedConstraintTest extends TestCase
{
    public function testNothingLoggedIsAccepted(): void
    {
        self::assertSame([], MovementUnusedConstraint::validate(0));
    }

    public function testSomethingLoggedIsRefused(): void
    {
        self::assertSame(['id' => [MovementUnusedConstraint::IN_USE]], MovementUnusedConstraint::validate(3));
    }
}
