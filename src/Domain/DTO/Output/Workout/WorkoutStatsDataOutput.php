<?php

declare(strict_types=1);

namespace App\Domain\DTO\Output\Workout;

/**
 * What a workout amounts to. A total is null when no set measures it — a workout of push-ups
 * moved no load — rather than zero, which would be a load of nothing.
 */
final class WorkoutStatsDataOutput
{
    public int $workoutId;

    public int $setCount = 0;

    /**
     * Reps times load, over the sets carrying both. A unilateral set counts its reps per side, so
     * it counts twice.
     */
    public ?float $volumeInKilograms = null;

    public ?int $durationInSeconds = null;

    public ?int $distanceInMetres = null;

    /**
     * The muscles worked, the most worked first.
     *
     * @var list<WorkoutMuscleShareDataOutput>
     */
    public array $muscles = [];
}
