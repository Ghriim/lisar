<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Training;

use App\Domain\DTO\Input\Training\UpdateRoutineSetDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Domain\Validation\Constraint\Training\RoutineSetInRoutineConstraint;
use App\Domain\Validation\Constraint\Training\RoutineSetPrescriptionConstraint;
use App\Domain\Validation\Validator\Training\UpdateRoutineSetValidator;
use App\Tests\CurrentUserTrait;
use App\Tests\RoutineTreeTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class UpdateRoutineSetValidatorTest extends TestCase
{
    use CurrentUserTrait;
    use RoutineTreeTrait;

    private UpdateRoutineSetValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new UpdateRoutineSetValidator(Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator());
    }

    public function testItAcceptsRepsAndAShareOnABarbellMovement(): void
    {
        $this->expectNotToPerformAssertions();

        $routine = $this->routineOf($this->anyCurrentUser());
        $this->validator->validate($this->anyCurrentUser(), $routine, $this->routineSetIn($this->routineExerciseIn($this->routineBlockIn($routine))), new UpdateRoutineSetDataInput(5, 80.0));
    }

    /** Someone else's routine: refused on that alone, whatever else the call carries. */
    public function testItRefusesSomeoneElsesRoutine(): void
    {
        $routine = $this->routineOf($this->anyCurrentUser());

        try {
            $this->validator->validate($this->currentUser(99), $routine, $this->routineSetIn($this->routineExerciseIn($this->routineBlockIn($routine))), new UpdateRoutineSetDataInput(5, 80.0));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(UpdateRoutineSetValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['currentUser' => [IsOwnerConstraint::NOT_ALLOWED]], $exception->violations);
        }
    }

    /** One of another of the caller's routines is answered like one that does not exist, and alone. */
    public function testItRefusesOneOfAnotherRoutine(): void
    {
        $routine = $this->routineOf($this->anyCurrentUser());
        $other = $this->routineOf($this->anyCurrentUser());

        try {
            $this->validator->validate($this->anyCurrentUser(), $routine, $this->routineSetIn($this->routineExerciseIn($this->routineBlockIn($other))), new UpdateRoutineSetDataInput());
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(UpdateRoutineSetValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['setId' => [RoutineSetInRoutineConstraint::NOT_FOUND]], $exception->violations);
        }
    }

    /** @return iterable<string, array{UpdateRoutineSetDataInput, string, string}> */
    public static function outOfBounds(): iterable
    {
        yield 'no rep' => [new UpdateRoutineSetDataInput(0, 80.0), 'reps', 'reps_invalid'];
        yield 'a share under one' => [new UpdateRoutineSetDataInput(5, 0.5), 'percentageOfOneRepMax', 'percentage_invalid'];
        yield 'a share past a hundred' => [new UpdateRoutineSetDataInput(5, 100.5), 'percentageOfOneRepMax', 'percentage_invalid'];
        yield 'a share off the halves' => [new UpdateRoutineSetDataInput(5, 72.3), 'percentageOfOneRepMax', 'percentage_invalid'];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('outOfBounds')]
    public function testItRejectsAValueOutOfBounds(UpdateRoutineSetDataInput $input, string $field, string $code): void
    {
        $routine = $this->routineOf($this->anyCurrentUser());

        try {
            $this->validator->validate($this->anyCurrentUser(), $routine, $this->routineSetIn($this->routineExerciseIn($this->routineBlockIn($routine))), $input);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(UpdateRoutineSetValidator::ERROR_CODE, $exception->errorCode);
            self::assertContains($code, $exception->violations[$field]);
        }
    }

    public function testItAccumulatesWhatTheMovementCallsFor(): void
    {
        $routine = $this->routineOf($this->anyCurrentUser());

        try {
            $this->validator->validate($this->anyCurrentUser(), $routine, $this->routineSetIn($this->routineExerciseIn($this->routineBlockIn($routine))), new UpdateRoutineSetDataInput());
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(UpdateRoutineSetValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['reps' => [RoutineSetPrescriptionConstraint::REPS_REQUIRED], 'percentageOfOneRepMax' => [RoutineSetPrescriptionConstraint::PERCENTAGE_REQUIRED]], $exception->violations);
        }
    }
}
