<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Constraint\Training;

use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\Validation\Constraint\Training\WorkoutExerciseInWorkoutConstraint;
use App\Tests\WorkoutTreeTrait;
use PHPUnit\Framework\TestCase;

final class WorkoutExerciseInWorkoutConstraintTest extends TestCase
{
    use WorkoutTreeTrait;

    public function testAMovementOfTheWorkoutPasses(): void
    {
        $workout = new WorkoutDataModel();

        self::assertSame([], WorkoutExerciseInWorkoutConstraint::validate($this->exerciseIn($workout), $workout));
    }

    public function testAMovementOfAnotherWorkoutIsNotFound(): void
    {
        self::assertSame(
            ['exerciseId' => [WorkoutExerciseInWorkoutConstraint::NOT_FOUND]],
            WorkoutExerciseInWorkoutConstraint::validate($this->exerciseIn(new WorkoutDataModel()), new WorkoutDataModel()),
        );
    }

    public function testItAddsToTheViolationsAlreadyThere(): void
    {
        $violations = WorkoutExerciseInWorkoutConstraint::validate($this->exerciseIn(new WorkoutDataModel()), new WorkoutDataModel(), ['name' => ['name_too_long']]);

        self::assertSame(['name_too_long'], $violations['name']);
        self::assertSame([WorkoutExerciseInWorkoutConstraint::NOT_FOUND], $violations['exerciseId']);
    }
}
