<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Training;

use App\Domain\DTO\Input\Training\AddRoutineBlockDataInput;
use App\Domain\DTO\Input\Training\AddRoutineBlockExerciseDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Domain\Validation\Constraint\Training\RoutineBlockMovementsDistinctConstraint;
use App\Domain\Validation\Constraint\Training\RoutineMovementsSupportedConstraint;
use App\Domain\Validation\Constraint\Training\WorkoutMovementsOfferedConstraint;
use App\Domain\Validation\Validator\Training\AddRoutineBlockValidator;
use App\Tests\CurrentUserTrait;
use App\Tests\RoutineTreeTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class AddRoutineBlockValidatorTest extends TestCase
{
    use CurrentUserTrait;
    use RoutineTreeTrait;

    private AddRoutineBlockValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new AddRoutineBlockValidator(Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator());
    }

    public function testItAcceptsOfferedMovementsThatFitARoutine(): void
    {
        $this->expectNotToPerformAssertions();

        $routine = $this->routineOf($this->anyCurrentUser());
        $this->validator->validate($this->anyCurrentUser(), $routine, $this->block(1, 2), [$this->barbellMovement(1), $this->bodyweightMovement(2)]);
    }

    /** Someone else's routine: refused on that alone, whatever else the call carries. */
    public function testItRefusesSomeoneElsesRoutine(): void
    {
        $routine = $this->routineOf($this->anyCurrentUser());

        try {
            $this->validator->validate($this->currentUser(99), $routine, new AddRoutineBlockDataInput(), []);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(AddRoutineBlockValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['currentUser' => [IsOwnerConstraint::NOT_ALLOWED]], $exception->violations);
        }
    }

    public function testItRejectsAMovementNotOffered(): void
    {
        $routine = $this->routineOf($this->anyCurrentUser());

        try {
            $this->validator->validate($this->anyCurrentUser(), $routine, $this->block(1, 2), [$this->barbellMovement(1)]);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(AddRoutineBlockValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['exercises' => [WorkoutMovementsOfferedConstraint::UNAVAILABLE]], $exception->violations);
        }
    }

    public function testItRejectsAMovementTrackingADistance(): void
    {
        $routine = $this->routineOf($this->anyCurrentUser());

        try {
            $this->validator->validate($this->anyCurrentUser(), $routine, $this->block(3), [$this->carryMovement(3)]);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(AddRoutineBlockValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['exercises' => [RoutineMovementsSupportedConstraint::NOT_SUPPORTED]], $exception->violations);
        }
    }

    public function testItAccumulatesEveryViolation(): void
    {
        $routine = $this->routineOf($this->anyCurrentUser());
        $input = new AddRoutineBlockDataInput([new AddRoutineBlockExerciseDataInput(1, 0), new AddRoutineBlockExerciseDataInput(1)]);

        try {
            $this->validator->validate($this->anyCurrentUser(), $routine, $input, [$this->barbellMovement(1)]);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(['rest_invalid'], $exception->violations['exercises[0].restInSeconds']);
            self::assertSame([RoutineBlockMovementsDistinctConstraint::DUPLICATED], $exception->violations['exercises']);
        }
    }

    public function testItRequiresAMovementAndTakesSixAtMost(): void
    {
        $routine = $this->routineOf($this->anyCurrentUser());

        try {
            $this->validator->validate($this->anyCurrentUser(), $routine, new AddRoutineBlockDataInput(), []);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(AddRoutineBlockValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['exercises' => ['exercises_required']], $exception->violations);
        }

        try {
            $this->validator->validate($this->anyCurrentUser(), $routine, $this->block(1, 2, 3, 4, 5, 6, 7), []);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertContains('exercises_too_many', $exception->violations['exercises']);
        }
    }

    private function block(int ...$movementIds): AddRoutineBlockDataInput
    {
        return new AddRoutineBlockDataInput(array_map(static fn (int $id): AddRoutineBlockExerciseDataInput => new AddRoutineBlockExerciseDataInput($id), $movementIds));
    }
}
