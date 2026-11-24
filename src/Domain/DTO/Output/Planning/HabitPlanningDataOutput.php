<?php

declare(strict_types=1);

namespace App\Domain\DTO\Output\Planning;

/** The habit planning over some days: every entry of the person's, and the dates they give. */
final class HabitPlanningDataOutput
{
    /** @var list<HabitPlanningEntryDataOutput> */
    public array $entries = [];

    /**
     * By day, the planned dates by start time — those within the day first — then the days kept
     * unplanned.
     *
     * @var list<HabitPlanningSlotDataOutput>
     */
    public array $slots = [];
}
