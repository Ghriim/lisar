<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Constraint\Training;

use App\Domain\DTO\DataModel\Training\RoutineDataModel;
use App\Domain\Validation\Constraint\Training\RoutineBlockOrderConstraint;
use App\Tests\CurrentUserTrait;
use App\Tests\RoutineTreeTrait;
use PHPUnit\Framework\TestCase;

final class RoutineBlockOrderConstraintTest extends TestCase
{
    use CurrentUserTrait;
    use RoutineTreeTrait;

    public function testEveryBlockInAnyOrderIsAccepted(): void
    {
        self::assertSame([], RoutineBlockOrderConstraint::validate([3, 1, 2], $this->routineWith(1, 2, 3)));
    }

    public function testABlockLeftOutIsRefused(): void
    {
        self::assertSame(['blockIds' => [RoutineBlockOrderConstraint::MISMATCH]], RoutineBlockOrderConstraint::validate([1, 2], $this->routineWith(1, 2, 3)));
    }

    public function testABlockOfAnotherRoutineIsRefused(): void
    {
        self::assertArrayHasKey('blockIds', RoutineBlockOrderConstraint::validate([1, 2, 9], $this->routineWith(1, 2, 3)));
    }

    public function testABlockNamedTwiceIsRefused(): void
    {
        self::assertArrayHasKey('blockIds', RoutineBlockOrderConstraint::validate([1, 1, 2, 3], $this->routineWith(1, 2, 3)));
    }

    private function routineWith(int ...$blockIds): RoutineDataModel
    {
        $routine = $this->routineOf($this->anyCurrentUser());
        foreach ($blockIds as $position => $id) {
            $this->routineBlockIn($routine, $position, $id);
        }

        return $routine;
    }
}
