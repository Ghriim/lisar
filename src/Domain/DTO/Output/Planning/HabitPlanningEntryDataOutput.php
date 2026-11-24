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

    /** One of HabitIconRegistry's codes. */
    public string $habitIcon;

    public PlanningScheduleDataOutput $schedule;
}
