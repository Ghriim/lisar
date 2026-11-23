<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Constraint\Training;

use App\Domain\Validation\Constraint\Training\RoutineBlockMovementsDistinctConstraint;
use PHPUnit\Framework\TestCase;

final class RoutineBlockMovementsDistinctConstraintTest extends TestCase
{
    public function testDifferentMovementsPass(): void
    {
        self::assertSame([], RoutineBlockMovementsDistinctConstraint::validate([4, 7]));
    }

    public function testTheSameMovementTwiceInABlockIsRefused(): void
    {
        self::assertSame(['exercises' => [RoutineBlockMovementsDistinctConstraint::DUPLICATED]], RoutineBlockMovementsDistinctConstraint::validate([4, 7, 4]));
    }
}
