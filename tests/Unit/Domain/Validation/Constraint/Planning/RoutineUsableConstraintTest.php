<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Constraint\Planning;

use App\Domain\Validation\Constraint\Planning\RoutineUsableConstraint;
use App\Tests\CurrentUserTrait;
use App\Tests\RoutineTreeTrait;
use PHPUnit\Framework\TestCase;

final class RoutineUsableConstraintTest extends TestCase
{
    use CurrentUserTrait;
    use RoutineTreeTrait;

    public function testTheCallersOwnRoutineIsAccepted(): void
    {
        self::assertSame([], RoutineUsableConstraint::validate($this->routineOf($this->anyCurrentUser()), $this->anyCurrentUser()));
    }

    /** Missing, someone else's, or shared from the back-office: all answered as not found. */
    public function testAnyOtherIsAnsweredAsNotFound(): void
    {
        $refused = ['routineId' => [RoutineUsableConstraint::ROUTINE_NOT_FOUND]];

        self::assertSame($refused, RoutineUsableConstraint::validate(null, $this->anyCurrentUser()));
        self::assertSame($refused, RoutineUsableConstraint::validate($this->routineOf($this->currentUser(99)), $this->anyCurrentUser()));
        self::assertSame($refused, RoutineUsableConstraint::validate($this->routineOf(null), $this->anyCurrentUser()));
    }
}
