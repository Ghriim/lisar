<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Constraint\Workout;

use App\Domain\Validation\Constraint\Workout\SetTypeUnusedConstraint;
use PHPUnit\Framework\TestCase;

final class SetTypeUnusedConstraintTest extends TestCase
{
    public function testNothingLoggedIsAccepted(): void
    {
        self::assertSame([], SetTypeUnusedConstraint::validate(0));
    }

    public function testSomethingLoggedIsRefused(): void
    {
        self::assertSame(['id' => [SetTypeUnusedConstraint::IN_USE]], SetTypeUnusedConstraint::validate(3));
    }
}
