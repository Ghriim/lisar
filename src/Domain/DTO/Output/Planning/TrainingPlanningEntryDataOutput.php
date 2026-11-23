<?php

declare(strict_types=1);

namespace App\Domain\DTO\Output\Planning;

/** A routine planned on a rule of dates. */
final class TrainingPlanningEntryDataOutput
{
    public int $id;

    public int $routineId;

    public string $routineName;

    public PlanningScheduleDataOutput $schedule;
}
