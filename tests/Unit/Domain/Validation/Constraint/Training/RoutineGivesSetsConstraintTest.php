<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Constraint\Training;

use App\Domain\Validation\Constraint\Training\RoutineGivesSetsConstraint;
use PHPUnit\Framework\TestCase;

final class RoutineGivesSetsConstraintTest extends TestCase
{
    public function testARoutineGivingASetIsAccepted(): void
    {
        self::assertSame([], RoutineGivesSetsConstraint::validate(1));
    }

    public function testARoutineGivingNoSetIsRefused(): void
    {
        self::assertSame(['routine' => [RoutineGivesSetsConstraint::EMPTY_ROUTINE]], RoutineGivesSetsConstraint::validate(0));
    }

    public function testItAddsToTheViolationsAlreadyThere(): void
    {
        $violations = RoutineGivesSetsConstraint::validate(0, ['workout' => ['workout_already_in_progress']]);

        self::assertSame(['workout_already_in_progress'], $violations['workout']);
        self::assertArrayHasKey('routine', $violations);
    }
}
