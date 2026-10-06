<?php

declare(strict_types=1);

namespace App\Domain\DTO\Output\Workout;

/** One step of a workout; more than one exercise makes it a superset. */
final class WorkoutBlockDataOutput
{
    public int $id;

    /** @var list<WorkoutExerciseDataOutput> */
    public array $exercises = [];
}
