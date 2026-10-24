<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Training;

use App\Domain\DTO\DataModel\Training\MovementDataModel;
use App\Domain\DTO\DataModel\Training\SetTypeDataModel;
use App\Domain\DTO\Input\Training\AddWorkoutSetDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Domain\Validation\Constraint\Training\WorkoutExerciseInWorkoutConstraint;
use App\Domain\Validation\Constraint\Training\WorkoutSetMeasuresConstraint;
use App\Domain\Validation\Constraint\Training\WorkoutSetTypeUsableConstraint;
use App\Domain\Validation\Constraint\Training\WorkoutSetWeightPresentConstraint;
use App\Domain\Validation\Validator\Training\AddWorkoutSetValidator;
use App\Tests\CurrentUserTrait;
use App\Tests\WorkoutTreeTrait;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

/** On a barbell bench press: reps and a weight, nothing else. */
final class AddWorkoutSetValidatorTest extends TestCase
{
    use CurrentUserTrait;
    use WorkoutTreeTrait;

    private AddWorkoutSetValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new AddWorkoutSetValidator(Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator());
    }

    public function testItAcceptsAFullSet(): void
    {
        $this->validate(new AddWorkoutSetDataInput(reps: 8, weightInKilograms: 62.5, rpe: 8.5, setTypeId: 1), $this->setType(true));

        $this->expectNotToPerformAssertions();
    }

    /** Someone else's workout: refused on that alone, whatever else the call carries. */
    public function testItRefusesSomeoneElsesWorkout(): void
    {
        try {
            $workout = $this->workoutOf($this->anyCurrentUser());
            $this->validator->validate($this->currentUser(99), $workout, $this->exerciseIn($workout, $this->bench()), new AddWorkoutSetDataInput(reps: 8, weightInKilograms: 62.5, rpe: 8.5, setTypeId: 1), $this->setType(true));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(AddWorkoutSetValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['currentUser' => [IsOwnerConstraint::NOT_ALLOWED]], $exception->violations);
        }
    }

    /** A movement of another of the caller's workouts is answered like one that does not exist, and alone. */
    public function testItRefusesAMovementOfAnotherWorkout(): void
    {
        $workout = $this->workoutOf($this->anyCurrentUser());
        $other = $this->workoutOf($this->anyCurrentUser());

        try {
            $this->validator->validate($this->anyCurrentUser(), $workout, $this->exerciseIn($other, $this->bench()), new AddWorkoutSetDataInput(reps: 0), null);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(AddWorkoutSetValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['exerciseId' => [WorkoutExerciseInWorkoutConstraint::NOT_FOUND]], $exception->violations);
        }
    }

    /** @return iterable<string, array{AddWorkoutSetDataInput, string, string}> */
    public static function outOfBounds(): iterable
    {
        yield 'no rep' => [new AddWorkoutSetDataInput(reps: 0, weightInKilograms: 60.0), 'reps', 'reps_invalid'];
        yield 'a negative load' => [new AddWorkoutSetDataInput(reps: 8, weightInKilograms: -1.0), 'weightInKilograms', 'load_invalid'];
        yield 'a load past a tonne' => [new AddWorkoutSetDataInput(reps: 8, weightInKilograms: 1000.5), 'weightInKilograms', 'load_invalid'];
        yield 'an rpe under one' => [new AddWorkoutSetDataInput(reps: 8, weightInKilograms: 60.0, rpe: 0.5), 'rpe', 'rpe_invalid'];
        yield 'an rpe past ten' => [new AddWorkoutSetDataInput(reps: 8, weightInKilograms: 60.0, rpe: 10.5), 'rpe', 'rpe_invalid'];
        yield 'an rpe off the halves' => [new AddWorkoutSetDataInput(reps: 8, weightInKilograms: 60.0, rpe: 7.3), 'rpe', 'rpe_invalid'];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('outOfBounds')]
    public function testItRejectsAValueOutOfBounds(AddWorkoutSetDataInput $input, string $field, string $code): void
    {
        try {
            $this->validate($input, null);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(AddWorkoutSetValidator::ERROR_CODE, $exception->errorCode);
            self::assertContains($code, $exception->violations[$field]);
        }
    }

    /** The load may wait while the workout runs: the set is still to do. A measure not tracked may not. */
    public function testItLetsTheLoadWaitButRejectsAMeasureNotTracked(): void
    {
        try {
            $this->validate(new AddWorkoutSetDataInput(reps: 8, durationInSeconds: 30), null);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertArrayNotHasKey('weightInKilograms', $exception->violations);
            self::assertSame([WorkoutSetMeasuresConstraint::DURATION_NOT_TRACKED], $exception->violations['durationInSeconds']);
        }
    }

    /** A set added to a finished workout comes ticked: it needs its load at once. */
    public function testItRejectsASetWithoutItsLoadOnAFinishedWorkout(): void
    {
        $workout = $this->workoutOf($this->anyCurrentUser());
        $workout->finishedAt = new DateTimeImmutable('2026-09-30T17:00:00+00:00');

        try {
            $this->validator->validate($this->anyCurrentUser(), $workout, $this->exerciseIn($workout, $this->bench()), new AddWorkoutSetDataInput(reps: 8), null);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(['weightInKilograms' => [WorkoutSetWeightPresentConstraint::WEIGHT_REQUIRED]], $exception->violations);
        }
    }

    public function testItRejectsAnUnknownSetType(): void
    {
        try {
            $this->validate(new AddWorkoutSetDataInput(reps: 8, weightInKilograms: 60.0, setTypeId: 9), null);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame([WorkoutSetTypeUsableConstraint::UNKNOWN], $exception->violations['setTypeId']);
        }
    }

    public function testItAccumulatesEveryViolation(): void
    {
        try {
            $this->validate(new AddWorkoutSetDataInput(reps: 0, rpe: 11.0, setTypeId: 1), $this->setType(false));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(['reps_invalid'], $exception->violations['reps']);
            self::assertSame(['rpe_invalid'], $exception->violations['rpe']);
            self::assertSame([WorkoutSetTypeUsableConstraint::INACTIVE], $exception->violations['setTypeId']);
        }
    }

    private function validate(AddWorkoutSetDataInput $input, ?SetTypeDataModel $setType): void
    {
        $workout = $this->workoutOf($this->anyCurrentUser());
        $this->validator->validate($this->anyCurrentUser(), $workout, $this->exerciseIn($workout, $this->bench()), $input, $setType);
    }

    private function bench(): MovementDataModel
    {
        $movement = new MovementDataModel();
        $movement->tracksReps = true;
        $movement->tracksWeight = true;

        return $movement;
    }

    private function setType(bool $isActive): SetTypeDataModel
    {
        $setType = new SetTypeDataModel();
        $setType->id = 1;
        $setType->isActive = $isActive;

        return $setType;
    }
}
