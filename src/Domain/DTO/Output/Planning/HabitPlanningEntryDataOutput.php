<?php

declare(strict_types=1);

namespace App\Domain\DTO\Output\Planning;

/** The days a subscribed habit counts, on a rule of dates. */
final class HabitPlanningEntryDataOutput
{
    public int $id;

    /** The catalogue habit's id. */
    public int $habitId;

    public string $habitName;

    /** The habit's category's icon, one of CategoryIconRegistry's codes; null without a category. */
    public ?string $habitIcon = null;

    public PlanningScheduleDataOutput $schedule;
}
