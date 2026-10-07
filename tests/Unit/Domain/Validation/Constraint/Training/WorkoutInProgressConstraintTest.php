<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Constraint\Training;

use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\Validation\Constraint\Training\WorkoutInProgressConstraint;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class WorkoutInProgressConstraintTest extends TestCase
{
    public function testAWorkoutInProgressIsAccepted(): void
    {
        self::assertSame([], WorkoutInProgressConstraint::validate(new WorkoutDataModel()));
    }

    public function testAFinishedWorkoutIsRefused(): void
    {
        self::assertSame(
            ['workout' => [WorkoutInProgressConstraint::FINISHED]],
            WorkoutInProgressConstraint::validate($this->finished()),
        );
    }

    public function testItAddsToTheViolationsAlreadyThere(): void
    {
        $violations = WorkoutInProgressConstraint::validate($this->finished(), ['name' => ['name_too_long']]);

        self::assertSame(['name_too_long'], $violations['name']);
        self::assertArrayHasKey('workout', $violations);
    }

    private function finished(): WorkoutDataModel
    {
        $workout = new WorkoutDataModel();
        $workout->finishedAt = new DateTimeImmutable('2026-10-02 19:00:00');

        return $workout;
    }
}
