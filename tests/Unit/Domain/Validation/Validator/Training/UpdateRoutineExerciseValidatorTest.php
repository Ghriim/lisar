<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Training;

use App\Domain\DTO\Input\Training\UpdateRoutineExerciseDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Domain\Validation\Constraint\Training\RoutineExerciseInRoutineConstraint;
use App\Domain\Validation\Validator\Training\UpdateRoutineExerciseValidator;
use App\Tests\CurrentUserTrait;
use App\Tests\RoutineTreeTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class UpdateRoutineExerciseValidatorTest extends TestCase
{
    use CurrentUserTrait;
    use RoutineTreeTrait;

    private UpdateRoutineExerciseValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new UpdateRoutineExerciseValidator(Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator());
    }

    public function testItAcceptsANoteAndARest(): void
    {
        $this->expectNotToPerformAssertions();

        $routine = $this->routineOf($this->anyCurrentUser());
        $this->validator->validate($this->anyCurrentUser(), $routine, $this->routineExerciseIn($this->routineBlockIn($routine)), new UpdateRoutineExerciseDataInput('Pause en bas', 90));
    }

    /** Someone else's routine: refused on that alone, whatever else the call carries. */
    public function testItRefusesSomeoneElsesRoutine(): void
    {
        $routine = $this->routineOf($this->anyCurrentUser());

        try {
            $this->validator->validate($this->currentUser(99), $routine, $this->routineExerciseIn($this->routineBlockIn($routine)), new UpdateRoutineExerciseDataInput('Pause en bas', 90));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(UpdateRoutineExerciseValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['currentUser' => [IsOwnerConstraint::NOT_ALLOWED]], $exception->violations);
        }
    }

    /** One of another of the caller's routines is answered like one that does not exist, and alone. */
    public function testItRefusesOneOfAnotherRoutine(): void
    {
        $routine = $this->routineOf($this->anyCurrentUser());
        $other = $this->routineOf($this->anyCurrentUser());

        try {
            $this->validator->validate($this->anyCurrentUser(), $routine, $this->routineExerciseIn($this->routineBlockIn($other)), new UpdateRoutineExerciseDataInput());
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(UpdateRoutineExerciseValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['exerciseId' => [RoutineExerciseInRoutineConstraint::NOT_FOUND]], $exception->violations);
        }
    }

    public function testItAccumulatesANoteTooLongAndARestOutOfBounds(): void
    {
        $routine = $this->routineOf($this->anyCurrentUser());

        try {
            $this->validator->validate($this->anyCurrentUser(), $routine, $this->routineExerciseIn($this->routineBlockIn($routine)), new UpdateRoutineExerciseDataInput(str_repeat('a', 5001), 3601));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(UpdateRoutineExerciseValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['note' => ['note_too_long'], 'restInSeconds' => ['rest_invalid']], $exception->violations);
        }
    }
}
