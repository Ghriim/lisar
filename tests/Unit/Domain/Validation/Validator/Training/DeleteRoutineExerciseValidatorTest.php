<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Training;

use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Domain\Validation\Constraint\Training\RoutineExerciseInRoutineConstraint;
use App\Domain\Validation\Validator\Training\DeleteRoutineExerciseValidator;
use App\Tests\CurrentUserTrait;
use App\Tests\RoutineTreeTrait;
use PHPUnit\Framework\TestCase;

final class DeleteRoutineExerciseValidatorTest extends TestCase
{
    use CurrentUserTrait;
    use RoutineTreeTrait;

    private DeleteRoutineExerciseValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new DeleteRoutineExerciseValidator();
    }

    public function testOneOfItsOwnMovementsPasses(): void
    {
        $this->expectNotToPerformAssertions();

        $routine = $this->routineOf($this->anyCurrentUser());
        $this->validator->validate($this->anyCurrentUser(), $routine, $this->routineExerciseIn($this->routineBlockIn($routine)));
    }

    /** Someone else's routine: refused on that alone, whatever else the call carries. */
    public function testItRefusesSomeoneElsesRoutine(): void
    {
        $routine = $this->routineOf($this->anyCurrentUser());

        try {
            $this->validator->validate($this->currentUser(99), $routine, $this->routineExerciseIn($this->routineBlockIn($routine)));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(DeleteRoutineExerciseValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['currentUser' => [IsOwnerConstraint::NOT_ALLOWED]], $exception->violations);
        }
    }

    /** One of another of the caller's routines is answered like one that does not exist, and alone. */
    public function testItRefusesOneOfAnotherRoutine(): void
    {
        $routine = $this->routineOf($this->anyCurrentUser());
        $other = $this->routineOf($this->anyCurrentUser());

        try {
            $this->validator->validate($this->anyCurrentUser(), $routine, $this->routineExerciseIn($this->routineBlockIn($other)));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(DeleteRoutineExerciseValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['exerciseId' => [RoutineExerciseInRoutineConstraint::NOT_FOUND]], $exception->violations);
        }
    }
}
