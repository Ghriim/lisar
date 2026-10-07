<?php

declare(strict_types=1);

namespace App\Domain\DTO\Output\Training;

/** A movement as done in one workout, with its sets in order. */
final class WorkoutExerciseDataOutput
{
    public int $id;

    public WorkoutMovementDataOutput $movement;

    public ?string $note = null;

    /** @var list<WorkoutSetDataOutput> */
    public array $sets = [];
}
