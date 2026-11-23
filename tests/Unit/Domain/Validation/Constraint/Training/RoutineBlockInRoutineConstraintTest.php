<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Constraint\Training;

use App\Domain\Validation\Constraint\Training\RoutineBlockInRoutineConstraint;
use App\Tests\CurrentUserTrait;
use App\Tests\RoutineTreeTrait;
use PHPUnit\Framework\TestCase;

final class RoutineBlockInRoutineConstraintTest extends TestCase
{
    use CurrentUserTrait;
    use RoutineTreeTrait;

    public function testOneOfItsOwnPasses(): void
    {
        $routine = $this->routineOf($this->anyCurrentUser());

        self::assertSame([], RoutineBlockInRoutineConstraint::validate($this->routineBlockIn($routine), $routine));
    }

    /** Another routine's, the caller's own or not: answered like one that does not exist. */
    public function testOneOfAnotherRoutineIsNotFound(): void
    {
        $routine = $this->routineOf($this->anyCurrentUser());
        $other = $this->routineOf($this->anyCurrentUser());

        self::assertSame(['blockId' => [RoutineBlockInRoutineConstraint::NOT_FOUND]], RoutineBlockInRoutineConstraint::validate($this->routineBlockIn($other), $routine));
    }
}
