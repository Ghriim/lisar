<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Training;

use App\Domain\DTO\Input\Training\UpdateWorkoutExerciseDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Domain\Validation\Constraint\Training\WorkoutExerciseInWorkoutConstraint;
use App\Domain\Validation\Validator\Training\UpdateWorkoutExerciseValidator;
use App\Tests\CurrentUserTrait;
use App\Tests\WorkoutTreeTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class UpdateWorkoutExerciseValidatorTest extends TestCase
{
    use CurrentUserTrait;
    use WorkoutTreeTrait;

    private UpdateWorkoutExerciseValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new UpdateWorkoutExerciseValidator(Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator());
    }

    public function testItAcceptsANote(): void
    {
        $workout = $this->workoutOf($this->anyCurrentUser());
        $this->validator->validate($this->anyCurrentUser(), $workout, $this->exerciseIn($workout), new UpdateWorkoutExerciseDataInput('Coudes serrés'));

        $this->expectNotToPerformAssertions();
    }

    /** Someone else's workout: refused on that alone, whatever else the call carries. */
    public function testItRefusesSomeoneElsesWorkout(): void
    {
        try {
            $workout = $this->workoutOf($this->anyCurrentUser());
            $this->validator->validate($this->currentUser(99), $workout, $this->exerciseIn($workout), new UpdateWorkoutExerciseDataInput('Coudes serrés'));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(UpdateWorkoutExerciseValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['currentUser' => [IsOwnerConstraint::NOT_ALLOWED]], $exception->violations);
        }
    }

    /** A movement of another of the caller's workouts is answered like one that does not exist, and alone. */
    public function testItRefusesAMovementOfAnotherWorkout(): void
    {
        $workout = $this->workoutOf($this->anyCurrentUser());
        $other = $this->workoutOf($this->anyCurrentUser());

        try {
            $this->validator->validate($this->anyCurrentUser(), $workout, $this->exerciseIn($other), new UpdateWorkoutExerciseDataInput(str_repeat('a', 5001)));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(UpdateWorkoutExerciseValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['exerciseId' => [WorkoutExerciseInWorkoutConstraint::NOT_FOUND]], $exception->violations);
        }
    }

    public function testItRejectsANoteTooLong(): void
    {
        try {
            $workout = $this->workoutOf($this->anyCurrentUser());
            $this->validator->validate($this->anyCurrentUser(), $workout, $this->exerciseIn($workout), new UpdateWorkoutExerciseDataInput(str_repeat('a', 5001)));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(UpdateWorkoutExerciseValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['note_too_long'], $exception->violations['note']);
        }
    }

    public function testItAcceptsARest(): void
    {
        $workout = $this->workoutOf($this->anyCurrentUser());
        $this->validator->validate($this->anyCurrentUser(), $workout, $this->exerciseIn($workout), new UpdateWorkoutExerciseDataInput(restInSeconds: 90));

        $this->expectNotToPerformAssertions();
    }

    public function testItRejectsARestOutOfBounds(): void
    {
        foreach ([0, 3601] as $rest) {
            try {
                $workout = $this->workoutOf($this->anyCurrentUser());
                $this->validator->validate($this->anyCurrentUser(), $workout, $this->exerciseIn($workout), new UpdateWorkoutExerciseDataInput(restInSeconds: $rest));
                self::fail('Expected ValidationException');
            } catch (ValidationException $exception) {
                self::assertSame(['rest_invalid'], $exception->violations['restInSeconds']);
            }
        }
    }
}
