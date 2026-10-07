<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Constraint\Training;

use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\Validation\Constraint\Training\WorkoutNotInProgressConstraint;
use PHPUnit\Framework\TestCase;

final class WorkoutNotInProgressConstraintTest extends TestCase
{
    public function testNoWorkoutInProgressIsAccepted(): void
    {
        self::assertSame([], WorkoutNotInProgressConstraint::validate(null));
    }

    public function testAWorkoutInProgressIsRefused(): void
    {
        self::assertSame(
            ['workout' => [WorkoutNotInProgressConstraint::ALREADY_IN_PROGRESS]],
            WorkoutNotInProgressConstraint::validate(new WorkoutDataModel()),
        );
    }

    public function testItAddsToTheViolationsAlreadyThere(): void
    {
        $violations = WorkoutNotInProgressConstraint::validate(new WorkoutDataModel(), ['name' => ['name_too_long']]);

        self::assertSame(['name_too_long'], $violations['name']);
        self::assertArrayHasKey('workout', $violations);
    }
}
