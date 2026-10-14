<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Training;

use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Domain\Validation\Constraint\Training\WorkoutSetInWorkoutConstraint;
use App\Domain\Validation\Validator\Training\DeleteWorkoutSetValidator;
use App\Tests\CurrentUserTrait;
use App\Tests\WorkoutTreeTrait;
use PHPUnit\Framework\TestCase;

final class DeleteWorkoutSetValidatorTest extends TestCase
{
    use CurrentUserTrait;
    use WorkoutTreeTrait;

    public function testItLetsTheCallersOwnWorkoutThrough(): void
    {
        $this->expectNotToPerformAssertions();

        $workout = $this->workoutOf($this->currentUser(1));
        (new DeleteWorkoutSetValidator())->validate($this->currentUser(1), $workout, $this->setIn($workout));
    }

    public function testItRefusesSomeoneElsesWorkout(): void
    {
        try {
            $workout = $this->workoutOf($this->currentUser(2));
            (new DeleteWorkoutSetValidator())->validate($this->currentUser(1), $workout, $this->setIn($workout));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(DeleteWorkoutSetValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['currentUser' => [IsOwnerConstraint::NOT_ALLOWED]], $exception->violations);
        }
    }

    /** A set of another of the caller's workouts is answered like one that does not exist, and alone. */
    public function testItRefusesASetOfAnotherWorkout(): void
    {
        $workout = $this->workoutOf($this->currentUser(1));
        $other = $this->workoutOf($this->currentUser(1));

        try {
            (new DeleteWorkoutSetValidator())->validate($this->currentUser(1), $workout, $this->setIn($other));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(DeleteWorkoutSetValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['setId' => [WorkoutSetInWorkoutConstraint::NOT_FOUND]], $exception->violations);
        }
    }
}
