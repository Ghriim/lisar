<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Constraint\Training;

use App\Domain\DTO\DataModel\Training\WorkoutBlockDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutExerciseDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutSetDataModel;
use App\Domain\Validation\Constraint\Training\WorkoutFinishableConstraint;
use PHPUnit\Framework\TestCase;

final class WorkoutFinishableConstraintTest extends TestCase
{
    public function testAWorkoutWithItsSetsDoneIsFinishable(): void
    {
        self::assertSame([], WorkoutFinishableConstraint::validate($this->workout(sets: [true, true])));
    }

    public function testAWorkoutWithoutAnyBlockIsRefused(): void
    {
        self::assertSame(['workout' => [WorkoutFinishableConstraint::EMPTY]], WorkoutFinishableConstraint::validate(new WorkoutDataModel()));
    }

    /** A movement added but never done is no workout either. */
    public function testAWorkoutWithAMovementButNoSetIsRefused(): void
    {
        self::assertSame(['workout' => [WorkoutFinishableConstraint::EMPTY]], WorkoutFinishableConstraint::validate($this->workout(sets: [])));
    }

    /** A set left to do is still to do, or to remove: the workout is not over. */
    public function testAWorkoutWithASetNotDoneIsRefused(): void
    {
        self::assertSame(
            ['workout' => [WorkoutFinishableConstraint::INCOMPLETE_SETS]],
            WorkoutFinishableConstraint::validate($this->workout(sets: [true, false])),
        );
    }

    public function testItAddsToTheViolationsAlreadyThere(): void
    {
        $violations = WorkoutFinishableConstraint::validate($this->workout(sets: [false]), ['name' => ['name_too_long']]);

        self::assertSame(['name_too_long'], $violations['name']);
        self::assertSame([WorkoutFinishableConstraint::INCOMPLETE_SETS], $violations['workout']);
    }

    /** @param list<bool> $sets whether each set is done */
    private function workout(array $sets): WorkoutDataModel
    {
        $workout = new WorkoutDataModel();
        $block = new WorkoutBlockDataModel();
        $exercise = new WorkoutExerciseDataModel();
        $workout->blocks->add($block);
        $block->exercises->add($exercise);
        foreach ($sets as $isComplete) {
            $set = new WorkoutSetDataModel();
            $set->isComplete = $isComplete;
            $exercise->sets->add($set);
        }

        return $workout;
    }
}
