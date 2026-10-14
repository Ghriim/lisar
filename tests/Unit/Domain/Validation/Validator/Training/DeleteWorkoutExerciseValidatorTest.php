<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Training;

use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Domain\Validation\Constraint\Training\WorkoutExerciseInWorkoutConstraint;
use App\Domain\Validation\Validator\Training\DeleteWorkoutExerciseValidator;
use App\Tests\CurrentUserTrait;
use App\Tests\WorkoutTreeTrait;
use PHPUnit\Framework\TestCase;

final class DeleteWorkoutExerciseValidatorTest extends TestCase
{
    use CurrentUserTrait;
    use WorkoutTreeTrait;

    public function testItLetsTheCallersOwnWorkoutThrough(): void
    {
        $this->expectNotToPerformAssertions();

        $workout = $this->workoutOf($this->currentUser(1));
        (new DeleteWorkoutExerciseValidator())->validate($this->currentUser(1), $workout, $this->exerciseIn($workout));
    }

    public function testItRefusesSomeoneElsesWorkout(): void
    {
        try {
            $workout = $this->workoutOf($this->currentUser(2));
            (new DeleteWorkoutExerciseValidator())->validate($this->currentUser(1), $workout, $this->exerciseIn($workout));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(DeleteWorkoutExerciseValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['currentUser' => [IsOwnerConstraint::NOT_ALLOWED]], $exception->violations);
        }
    }

    /** A movement of another of the caller's workouts is answered like one that does not exist, and alone. */
    public function testItRefusesAMovementOfAnotherWorkout(): void
    {
        $workout = $this->workoutOf($this->currentUser(1));
        $other = $this->workoutOf($this->currentUser(1));

        try {
            (new DeleteWorkoutExerciseValidator())->validate($this->currentUser(1), $workout, $this->exerciseIn($other));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(DeleteWorkoutExerciseValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['exerciseId' => [WorkoutExerciseInWorkoutConstraint::NOT_FOUND]], $exception->violations);
        }
    }
}
