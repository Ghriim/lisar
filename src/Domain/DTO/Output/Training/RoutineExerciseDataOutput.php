<?php

declare(strict_types=1);

namespace App\Domain\DTO\Output\Training;

/** A movement as a routine prescribes it, with its sets in order. */
final class RoutineExerciseDataOutput
{
    public int $id;

    /** What a workout needs to know of a movement: a routine needs the same. */
    public WorkoutMovementDataOutput $movement;

    public ?string $note = null;

    public ?int $restInSeconds = null;

    /** @var list<RoutineSetDataOutput> */
    public array $sets = [];
}
