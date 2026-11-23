<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Training;

use App\Domain\DTO\Input\Training\AddRoutineSetDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Domain\Validation\Constraint\Training\RoutineExerciseInRoutineConstraint;
use App\Domain\Validation\Constraint\Training\RoutineSetPrescriptionConstraint;
use App\Domain\Validation\Validator\Training\AddRoutineSetValidator;
use App\Tests\CurrentUserTrait;
use App\Tests\RoutineTreeTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class AddRoutineSetValidatorTest extends TestCase
{
    use CurrentUserTrait;
    use RoutineTreeTrait;

    private AddRoutineSetValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new AddRoutineSetValidator(Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator());
    }

    public function testItAcceptsRepsAndAShareOnABarbellMovement(): void
    {
        $this->expectNotToPerformAssertions();

        $routine = $this->routineOf($this->anyCurrentUser());
        $this->validator->validate($this->anyCurrentUser(), $routine, $this->routineExerciseIn($this->routineBlockIn($routine)), new AddRoutineSetDataInput(5, 80.0));
    }

    /** Someone else's routine: refused on that alone, whatever else the call carries. */
    public function testItRefusesSomeoneElsesRoutine(): void
    {
        $routine = $this->routineOf($this->anyCurrentUser());

        try {
            $this->validator->validate($this->currentUser(99), $routine, $this->routineExerciseIn($this->routineBlockIn($routine)), new AddRoutineSetDataInput(5, 80.0));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(AddRoutineSetValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['currentUser' => [IsOwnerConstraint::NOT_ALLOWED]], $exception->violations);
        }
    }

    /** One of another of the caller's routines is answered like one that does not exist, and alone. */
    public function testItRefusesOneOfAnotherRoutine(): void
    {
        $routine = $this->routineOf($this->anyCurrentUser());
        $other = $this->routineOf($this->anyCurrentUser());

        try {
            $this->validator->validate($this->anyCurrentUser(), $routine, $this->routineExerciseIn($this->routineBlockIn($other)), new AddRoutineSetDataInput());
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(AddRoutineSetValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['exerciseId' => [RoutineExerciseInRoutineConstraint::NOT_FOUND]], $exception->violations);
        }
    }

    /** @return iterable<string, array{AddRoutineSetDataInput, string, string}> */
    public static function outOfBounds(): iterable
    {
        yield 'no rep' => [new AddRoutineSetDataInput(0, 80.0), 'reps', 'reps_invalid'];
        yield 'a share under one' => [new AddRoutineSetDataInput(5, 0.5), 'percentageOfOneRepMax', 'percentage_invalid'];
        yield 'a share past a hundred' => [new AddRoutineSetDataInput(5, 100.5), 'percentageOfOneRepMax', 'percentage_invalid'];
        yield 'a share off the halves' => [new AddRoutineSetDataInput(5, 72.3), 'percentageOfOneRepMax', 'percentage_invalid'];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('outOfBounds')]
    public function testItRejectsAValueOutOfBounds(AddRoutineSetDataInput $input, string $field, string $code): void
    {
        $routine = $this->routineOf($this->anyCurrentUser());

        try {
            $this->validator->validate($this->anyCurrentUser(), $routine, $this->routineExerciseIn($this->routineBlockIn($routine)), $input);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(AddRoutineSetValidator::ERROR_CODE, $exception->errorCode);
            self::assertContains($code, $exception->violations[$field]);
        }
    }

    public function testItAccumulatesWhatTheMovementCallsFor(): void
    {
        $routine = $this->routineOf($this->anyCurrentUser());

        try {
            $this->validator->validate($this->anyCurrentUser(), $routine, $this->routineExerciseIn($this->routineBlockIn($routine)), new AddRoutineSetDataInput());
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(AddRoutineSetValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['reps' => [RoutineSetPrescriptionConstraint::REPS_REQUIRED], 'percentageOfOneRepMax' => [RoutineSetPrescriptionConstraint::PERCENTAGE_REQUIRED]], $exception->violations);
        }
    }
}
