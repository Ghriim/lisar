<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Training;

use App\Domain\DTO\DataModel\Training\WorkoutBlockDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutExerciseDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutSetDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Domain\Validation\Constraint\Training\WorkoutFinishableConstraint;
use App\Domain\Validation\Validator\Training\FinishWorkoutValidator;
use App\Tests\CurrentUserTrait;
use PHPUnit\Framework\TestCase;

final class FinishWorkoutValidatorTest extends TestCase
{
    use CurrentUserTrait;

    public function testItLetsAValidCaseThrough(): void
    {
        $this->expectNotToPerformAssertions();

        (new FinishWorkoutValidator())->validate($this->anyCurrentUser(), $this->workout(true));
    }

    /** Someone else's workout: refused on that alone, whatever else the call carries. */
    public function testItRefusesSomeoneElsesWorkout(): void
    {
        try {
            (new FinishWorkoutValidator())->validate($this->currentUser(99), $this->workout(true));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(FinishWorkoutValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['currentUser' => [IsOwnerConstraint::NOT_ALLOWED]], $exception->violations);
        }
    }

    public function testItRefusesAnEmptyWorkout(): void
    {
        $this->assertRefused(['workout' => [WorkoutFinishableConstraint::EMPTY]], fn () => (new FinishWorkoutValidator())->validate($this->anyCurrentUser(), $this->workout(null)));
    }

    public function testItRefusesAWorkoutWithASetStillToDo(): void
    {
        $this->assertRefused(['workout' => [WorkoutFinishableConstraint::INCOMPLETE_SETS]], fn () => (new FinishWorkoutValidator())->validate($this->anyCurrentUser(), $this->workout(false)));
    }

    /** @param array<string, list<string>> $violations */
    private function assertRefused(array $violations, callable $validate): void
    {
        try {
            $validate();
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(FinishWorkoutValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame($violations, $exception->violations);
        }
    }

    private function workout(?bool $setIsComplete): WorkoutDataModel
    {
        $workout = new WorkoutDataModel();
        $workout->owner = $this->anyCurrentUser();
        if (null !== $setIsComplete) {
            $block = new WorkoutBlockDataModel();
            $exercise = new WorkoutExerciseDataModel();
            $set = new WorkoutSetDataModel();
            $set->isComplete = $setIsComplete;
            $exercise->sets->add($set);
            $block->exercises->add($exercise);
            $workout->blocks->add($block);
        }

        return $workout;
    }
}
