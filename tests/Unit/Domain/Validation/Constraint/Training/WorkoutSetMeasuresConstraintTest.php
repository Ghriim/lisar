<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Constraint\Training;

use App\Domain\DTO\DataModel\Training\MovementDataModel;
use App\Domain\Validation\Constraint\Training\WorkoutSetMeasuresConstraint;
use PHPUnit\Framework\TestCase;

final class WorkoutSetMeasuresConstraintTest extends TestCase
{
    public function testExactlyTheTrackedMeasuresAreAccepted(): void
    {
        self::assertSame([], WorkoutSetMeasuresConstraint::validate($this->movement(reps: true, weight: true), 8, 60.0, null, null));
    }

    /** Zero is a load: an empty machine, a bar on its own. */
    public function testAZeroWeightIsAWeight(): void
    {
        self::assertSame([], WorkoutSetMeasuresConstraint::validate($this->movement(reps: true, weight: true), 8, 0.0, null, null));
    }

    public function testEachTrackedMeasureMissingIsRefused(): void
    {
        self::assertSame(
            [
                'reps' => [WorkoutSetMeasuresConstraint::REPS_REQUIRED],
                'weightInKilograms' => [WorkoutSetMeasuresConstraint::WEIGHT_REQUIRED],
                'durationInSeconds' => [WorkoutSetMeasuresConstraint::DURATION_REQUIRED],
                'distanceInMetres' => [WorkoutSetMeasuresConstraint::DISTANCE_REQUIRED],
            ],
            WorkoutSetMeasuresConstraint::validate($this->movement(reps: true, weight: true, duration: true, distance: true), null, null, null, null),
        );
    }

    public function testEachUntrackedMeasureGivenIsRefused(): void
    {
        self::assertSame(
            [
                'weightInKilograms' => [WorkoutSetMeasuresConstraint::WEIGHT_NOT_TRACKED],
                'durationInSeconds' => [WorkoutSetMeasuresConstraint::DURATION_NOT_TRACKED],
                'distanceInMetres' => [WorkoutSetMeasuresConstraint::DISTANCE_NOT_TRACKED],
            ],
            WorkoutSetMeasuresConstraint::validate($this->movement(reps: true), 10, 20.0, 30, 400),
        );
    }

    public function testRepsGivenToAMovementThatDoesNotCountThemAreRefused(): void
    {
        self::assertSame(
            ['reps' => [WorkoutSetMeasuresConstraint::REPS_NOT_TRACKED]],
            WorkoutSetMeasuresConstraint::validate($this->movement(duration: true), 10, null, 60, null),
        );
    }

    private function movement(bool $reps = false, bool $weight = false, bool $duration = false, bool $distance = false): MovementDataModel
    {
        $movement = new MovementDataModel();
        $movement->tracksReps = $reps;
        $movement->tracksWeight = $weight;
        $movement->tracksDuration = $duration;
        $movement->tracksDistance = $distance;

        return $movement;
    }
}
