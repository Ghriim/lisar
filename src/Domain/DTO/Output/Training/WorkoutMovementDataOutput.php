<?php

declare(strict_types=1);

namespace App\Domain\DTO\Output\Training;

/**
 * What a workout needs to know of a movement: its name and what a set of it records. Its status
 * travels too — a movement retired since stays in the workouts that logged it.
 */
final class WorkoutMovementDataOutput
{
    public int $id;

    public string $name;

    public bool $tracksReps;

    public bool $tracksWeight;

    public bool $tracksDuration;

    public bool $tracksDistance;

    /** A set covers both sides, its reps counted per side. */
    public bool $isUnilateral;

    public bool $isActive;
}
