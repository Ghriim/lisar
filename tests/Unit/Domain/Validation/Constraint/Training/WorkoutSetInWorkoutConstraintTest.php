<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Constraint\Training;

use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\Validation\Constraint\Training\WorkoutSetInWorkoutConstraint;
use App\Tests\WorkoutTreeTrait;
use PHPUnit\Framework\TestCase;

final class WorkoutSetInWorkoutConstraintTest extends TestCase
{
    use WorkoutTreeTrait;

    public function testASetOfTheWorkoutPasses(): void
    {
        $workout = new WorkoutDataModel();

        self::assertSame([], WorkoutSetInWorkoutConstraint::validate($this->setIn($workout), $workout));
    }

    public function testASetOfAnotherWorkoutIsNotFound(): void
    {
        self::assertSame(
            ['setId' => [WorkoutSetInWorkoutConstraint::NOT_FOUND]],
            WorkoutSetInWorkoutConstraint::validate($this->setIn(new WorkoutDataModel()), new WorkoutDataModel()),
        );
    }

    public function testItAddsToTheViolationsAlreadyThere(): void
    {
        $violations = WorkoutSetInWorkoutConstraint::validate($this->setIn(new WorkoutDataModel()), new WorkoutDataModel(), ['name' => ['name_too_long']]);

        self::assertSame(['name_too_long'], $violations['name']);
        self::assertSame([WorkoutSetInWorkoutConstraint::NOT_FOUND], $violations['setId']);
    }
}
