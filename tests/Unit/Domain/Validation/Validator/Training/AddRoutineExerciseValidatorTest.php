<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Training;

use App\Domain\DTO\Input\Training\AddRoutineExerciseDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Domain\Validation\Constraint\Training\RoutineBlockInRoutineConstraint;
use App\Domain\Validation\Constraint\Training\RoutineMovementsSupportedConstraint;
use App\Domain\Validation\Constraint\Training\WorkoutMovementsOfferedConstraint;
use App\Domain\Validation\Validator\Training\AddRoutineExerciseValidator;
use App\Tests\CurrentUserTrait;
use App\Tests\RoutineTreeTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class AddRoutineExerciseValidatorTest extends TestCase
{
    use CurrentUserTrait;
    use RoutineTreeTrait;

    private AddRoutineExerciseValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new AddRoutineExerciseValidator(Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator());
    }

    public function testItAcceptsAnOfferedMovementThatFitsARoutine(): void
    {
        $this->expectNotToPerformAssertions();

        $routine = $this->routineOf($this->anyCurrentUser());
        $this->validator->validate($this->anyCurrentUser(), $routine, $this->routineBlockIn($routine), new AddRoutineExerciseDataInput(1), $this->barbellMovement(1));
    }

    /** Someone else's routine: refused on that alone, whatever else the call carries. */
    public function testItRefusesSomeoneElsesRoutine(): void
    {
        $routine = $this->routineOf($this->anyCurrentUser());

        try {
            $this->validator->validate($this->currentUser(99), $routine, $this->routineBlockIn($routine), new AddRoutineExerciseDataInput(1), $this->barbellMovement(1));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(AddRoutineExerciseValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['currentUser' => [IsOwnerConstraint::NOT_ALLOWED]], $exception->violations);
        }
    }

    /** One of another of the caller's routines is answered like one that does not exist, and alone. */
    public function testItRefusesOneOfAnotherRoutine(): void
    {
        $routine = $this->routineOf($this->anyCurrentUser());
        $other = $this->routineOf($this->anyCurrentUser());

        try {
            $this->validator->validate($this->anyCurrentUser(), $routine, $this->routineBlockIn($other), new AddRoutineExerciseDataInput(1), null);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(AddRoutineExerciseValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['blockId' => [RoutineBlockInRoutineConstraint::NOT_FOUND]], $exception->violations);
        }
    }

    public function testItRejectsAMovementNotOffered(): void
    {
        $routine = $this->routineOf($this->anyCurrentUser());

        try {
            $this->validator->validate($this->anyCurrentUser(), $routine, $this->routineBlockIn($routine), new AddRoutineExerciseDataInput(1), null);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(AddRoutineExerciseValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['movementId' => [WorkoutMovementsOfferedConstraint::UNAVAILABLE]], $exception->violations);
        }
    }

    public function testItAccumulatesARestOutOfBoundsAndAMovementThatDoesNotFit(): void
    {
        $routine = $this->routineOf($this->anyCurrentUser());

        try {
            $this->validator->validate($this->anyCurrentUser(), $routine, $this->routineBlockIn($routine), new AddRoutineExerciseDataInput(3, 0), $this->carryMovement(3));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(AddRoutineExerciseValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['restInSeconds' => ['rest_invalid'], 'movementId' => [RoutineMovementsSupportedConstraint::NOT_SUPPORTED]], $exception->violations);
        }
    }
}
