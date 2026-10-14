<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Constraint\Training;

use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\Validation\Constraint\Training\WorkoutBlockInWorkoutConstraint;
use App\Tests\WorkoutTreeTrait;
use PHPUnit\Framework\TestCase;

final class WorkoutBlockInWorkoutConstraintTest extends TestCase
{
    use WorkoutTreeTrait;

    public function testABlockOfTheWorkoutPasses(): void
    {
        $workout = new WorkoutDataModel();

        self::assertSame([], WorkoutBlockInWorkoutConstraint::validate($this->blockIn($workout), $workout));
    }

    public function testABlockOfAnotherWorkoutIsNotFound(): void
    {
        self::assertSame(
            ['blockId' => [WorkoutBlockInWorkoutConstraint::NOT_FOUND]],
            WorkoutBlockInWorkoutConstraint::validate($this->blockIn(new WorkoutDataModel()), new WorkoutDataModel()),
        );
    }

    public function testItAddsToTheViolationsAlreadyThere(): void
    {
        $violations = WorkoutBlockInWorkoutConstraint::validate($this->blockIn(new WorkoutDataModel()), new WorkoutDataModel(), ['name' => ['name_too_long']]);

        self::assertSame(['name_too_long'], $violations['name']);
        self::assertSame([WorkoutBlockInWorkoutConstraint::NOT_FOUND], $violations['blockId']);
    }
}
