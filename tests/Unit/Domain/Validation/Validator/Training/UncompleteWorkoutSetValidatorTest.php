<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Training;

use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Domain\Validation\Constraint\Training\WorkoutInProgressConstraint;
use App\Domain\Validation\Constraint\Training\WorkoutSetInWorkoutConstraint;
use App\Domain\Validation\Validator\Training\UncompleteWorkoutSetValidator;
use App\Tests\CurrentUserTrait;
use App\Tests\WorkoutTreeTrait;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class UncompleteWorkoutSetValidatorTest extends TestCase
{
    use CurrentUserTrait;
    use WorkoutTreeTrait;

    public function testItLetsAValidCaseThrough(): void
    {
        $this->expectNotToPerformAssertions();

        $workout = $this->workout(false);
        (new UncompleteWorkoutSetValidator())->validate($this->anyCurrentUser(), $workout, $this->setIn($workout));
    }

    /** Someone else's workout: refused on that alone, whatever else the call carries. */
    public function testItRefusesSomeoneElsesWorkout(): void
    {
        try {
            $workout = $this->workout(false);
            (new UncompleteWorkoutSetValidator())->validate($this->currentUser(99), $workout, $this->setIn($workout));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(UncompleteWorkoutSetValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['currentUser' => [IsOwnerConstraint::NOT_ALLOWED]], $exception->violations);
        }
    }

    /** A set of another of the caller's workouts is answered like one that does not exist, and alone. */
    public function testItRefusesASetOfAnotherWorkout(): void
    {
        $workout = $this->workout(true);
        $other = $this->workoutOf($this->anyCurrentUser());

        try {
            (new UncompleteWorkoutSetValidator())->validate($this->anyCurrentUser(), $workout, $this->setIn($other));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(UncompleteWorkoutSetValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['setId' => [WorkoutSetInWorkoutConstraint::NOT_FOUND]], $exception->violations);
        }
    }

    public function testItRefusesAFinishedWorkout(): void
    {
        $workout = $this->workout(true);
        $this->assertRefused(['workout' => [WorkoutInProgressConstraint::FINISHED]], fn () => (new UncompleteWorkoutSetValidator())->validate($this->anyCurrentUser(), $workout, $this->setIn($workout)));
    }

    /** @param array<string, list<string>> $violations */
    private function assertRefused(array $violations, callable $validate): void
    {
        try {
            $validate();
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(UncompleteWorkoutSetValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame($violations, $exception->violations);
        }
    }

    private function workout(bool $isFinished): WorkoutDataModel
    {
        $workout = new WorkoutDataModel();
        $workout->owner = $this->anyCurrentUser();
        $workout->finishedAt = true === $isFinished ? new DateTimeImmutable('2026-09-30T17:00:00+00:00') : null;

        return $workout;
    }
}
