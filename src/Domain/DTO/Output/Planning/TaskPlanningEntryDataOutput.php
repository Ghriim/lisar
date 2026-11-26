<?php

declare(strict_types=1);

namespace App\Domain\DTO\Output\Planning;

use App\Domain\DTO\Output\Category\CategoryDataOutput;

/** A task model planned on a rule of dates. */
final class TaskPlanningEntryDataOutput
{
    public int $id;

    public string $title;

    public ?string $description = null;

    public ?CategoryDataOutput $category = null;

    public PlanningScheduleDataOutput $schedule;
}
