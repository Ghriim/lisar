<?php

declare(strict_types=1);

namespace App\Domain\DTO\Output\Training;

/** One prescribed set: what it asks for, and the load that would give today. */
final class RoutineSetDataOutput
{
    public int $id;

    public ?int $reps = null;

    /** Null for a movement without a load. */
    public ?float $percentageOfOneRepMax = null;

    /**
     * The load a workout started now would give it, worked out from the caller's estimated 1RM.
     * Null without a share, or without an estimated 1RM yet.
     */
    public ?float $estimatedLoadInKilograms = null;
}
