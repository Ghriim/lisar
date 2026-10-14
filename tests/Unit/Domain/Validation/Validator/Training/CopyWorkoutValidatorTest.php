<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Training;

use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Domain\Validation\Constraint\Training\WorkoutNotInProgressConstraint;
use App\Domain\Validation\Validator\Training\CopyWorkoutValidator;
use App\Tests\CurrentUserTrait;
use PHPUnit\Framework\TestCase;

final class CopyWorkoutValidatorTest extends TestCase
{
    use CurrentUserTrait;

    public function testItLetsAValidCaseThrough(): void
    {
        $this->expectNotToPerformAssertions();

        (new CopyWorkoutValidator())->validate($this->anyCurrentUser(), $this->workoutOf($this->anyCurrentUser()), null);
    }

    /** Someone else's workout: refused on that alone, whatever else the call carries. */
    public function testItRefusesSomeoneElsesWorkout(): void
    {
        try {
            (new CopyWorkoutValidator())->validate($this->currentUser(99), $this->workoutOf($this->anyCurrentUser()), null);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(CopyWorkoutValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['currentUser' => [IsOwnerConstraint::NOT_ALLOWED]], $exception->violations);
        }
    }

    public function testItRefusesWhileAWorkoutIsInProgress(): void
    {
        $this->assertRefused(['workout' => [WorkoutNotInProgressConstraint::ALREADY_IN_PROGRESS]], fn () => (new CopyWorkoutValidator())->validate($this->anyCurrentUser(), $this->workoutOf($this->anyCurrentUser()), new WorkoutDataModel()));
    }

    /** @param array<string, list<string>> $violations */
    private function assertRefused(array $violations, callable $validate): void
    {
        try {
            $validate();
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(CopyWorkoutValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame($violations, $exception->violations);
        }
    }
}
