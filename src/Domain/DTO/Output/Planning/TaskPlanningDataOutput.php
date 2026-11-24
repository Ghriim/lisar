<?php

declare(strict_types=1);

namespace App\Domain\DTO\Output\Planning;

/** The task planning over some days: every entry of the person's, and the dates they give. */
final class TaskPlanningDataOutput
{
    /** @var list<TaskPlanningEntryDataOutput> */
    public array $entries = [];

    /**
     * By day, then by start time — those within the day first.
     *
     * @var list<TaskPlanningSlotDataOutput>
     */
    public array $slots = [];
}
