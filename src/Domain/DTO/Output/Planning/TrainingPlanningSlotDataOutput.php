<?php

declare(strict_types=1);

namespace App\Domain\DTO\Output\Planning;

/** One date of a training entry, and where it stands. */
final class TrainingPlanningSlotDataOutput
{
    public int $entryId;

    /** A calendar day, "YYYY-MM-DD". */
    public string $day;

    /** to_come, kept or missed. */
    public string $state;

    /** The workout that kept it; null otherwise. */
    public ?int $workoutId = null;
}
