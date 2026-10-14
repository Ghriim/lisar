<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Training;

use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Domain\Validation\Constraint\Training\WorkoutBlockInWorkoutConstraint;
use App\Domain\Validation\Validator\Training\DeleteWorkoutBlockValidator;
use App\Tests\CurrentUserTrait;
use App\Tests\WorkoutTreeTrait;
use PHPUnit\Framework\TestCase;

final class DeleteWorkoutBlockValidatorTest extends TestCase
{
    use CurrentUserTrait;
    use WorkoutTreeTrait;

    public function testItLetsTheCallersOwnWorkoutThrough(): void
    {
        $this->expectNotToPerformAssertions();

        $workout = $this->workoutOf($this->currentUser(1));
        (new DeleteWorkoutBlockValidator())->validate($this->currentUser(1), $workout, $this->blockIn($workout));
    }

    public function testItRefusesSomeoneElsesWorkout(): void
    {
        try {
            $workout = $this->workoutOf($this->currentUser(2));
            (new DeleteWorkoutBlockValidator())->validate($this->currentUser(1), $workout, $this->blockIn($workout));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(DeleteWorkoutBlockValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['currentUser' => [IsOwnerConstraint::NOT_ALLOWED]], $exception->violations);
        }
    }

    /** A block of another of the caller's workouts is answered like one that does not exist, and alone. */
    public function testItRefusesABlockOfAnotherWorkout(): void
    {
        $workout = $this->workoutOf($this->currentUser(1));
        $other = $this->workoutOf($this->currentUser(1));

        try {
            (new DeleteWorkoutBlockValidator())->validate($this->currentUser(1), $workout, $this->blockIn($other));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(DeleteWorkoutBlockValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['blockId' => [WorkoutBlockInWorkoutConstraint::NOT_FOUND]], $exception->violations);
        }
    }
}
