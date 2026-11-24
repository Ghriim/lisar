<?php

declare(strict_types=1);

namespace App\Domain\DTO\Output\Planning;

/** One date of a habit entry and where it stands — or a day the habit was kept unplanned. */
final class HabitPlanningSlotDataOutput
{
    /** The entry that asks for the day; null for a day kept unplanned. */
    public ?int $entryId = null;

    /** The catalogue habit's id. */
    public int $habitId;

    /** A calendar day, "YYYY-MM-DD". */
    public string $day;

    /** to_come, kept, missed or kept_unplanned. */
    public string $state;
}
