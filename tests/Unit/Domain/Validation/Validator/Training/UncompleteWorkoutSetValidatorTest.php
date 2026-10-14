<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Training;

use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Domain\Validation\Constraint\Training\WorkoutInProgressConstraint;
use App\Domain\Validation\Validator\Training\UncompleteWorkoutSetValidator;
use App\Tests\CurrentUserTrait;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class UncompleteWorkoutSetValidatorTest extends TestCase
{
    use CurrentUserTrait;

    public function testItLetsAValidCaseThrough(): void
    {
        $this->expectNotToPerformAssertions();

        (new UncompleteWorkoutSetValidator())->validate($this->anyCurrentUser(), $this->workout(false));
    }

    /** Someone else's workout: refused on that alone, whatever else the call carries. */
    public function testItRefusesSomeoneElsesWorkout(): void
    {
        try {
            (new UncompleteWorkoutSetValidator())->validate($this->currentUser(99), $this->workout(false));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(UncompleteWorkoutSetValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['currentUser' => [IsOwnerConstraint::NOT_ALLOWED]], $exception->violations);
        }
    }

    public function testItRefusesAFinishedWorkout(): void
    {
        $this->assertRefused(['workout' => [WorkoutInProgressConstraint::FINISHED]], fn () => (new UncompleteWorkoutSetValidator())->validate($this->anyCurrentUser(), $this->workout(true)));
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
