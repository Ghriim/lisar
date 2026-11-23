<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Constraint\Training;

use App\Domain\Validation\Constraint\Training\RoutineSetInRoutineConstraint;
use App\Tests\CurrentUserTrait;
use App\Tests\RoutineTreeTrait;
use PHPUnit\Framework\TestCase;

final class RoutineSetInRoutineConstraintTest extends TestCase
{
    use CurrentUserTrait;
    use RoutineTreeTrait;

    public function testOneOfItsOwnPasses(): void
    {
        $routine = $this->routineOf($this->anyCurrentUser());

        self::assertSame([], RoutineSetInRoutineConstraint::validate($this->routineSetIn($this->routineExerciseIn($this->routineBlockIn($routine))), $routine));
    }

    /** Another routine's, the caller's own or not: answered like one that does not exist. */
    public function testOneOfAnotherRoutineIsNotFound(): void
    {
        $routine = $this->routineOf($this->anyCurrentUser());
        $other = $this->routineOf($this->anyCurrentUser());

        self::assertSame(['setId' => [RoutineSetInRoutineConstraint::NOT_FOUND]], RoutineSetInRoutineConstraint::validate($this->routineSetIn($this->routineExerciseIn($this->routineBlockIn($other))), $routine));
    }
}
