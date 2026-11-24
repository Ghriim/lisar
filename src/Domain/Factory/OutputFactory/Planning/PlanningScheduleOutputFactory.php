<?php

declare(strict_types=1);

namespace App\Domain\Factory\OutputFactory\Planning;

use App\Domain\DataTransformer\DateDataTransformer;
use App\Domain\DataTransformer\TimeDataTransformer;
use App\Domain\DTO\DataModel\Planning\HabitPlanningEntryDataModel;
use App\Domain\DTO\DataModel\Planning\TaskPlanningEntryDataModel;
use App\Domain\DTO\DataModel\Planning\TrainingPlanningEntryDataModel;
use App\Domain\DTO\Output\Planning\PlanningScheduleDataOutput;

/** A planning entry's schedule, as it was saved: the same on every kind of entry. */
final readonly class PlanningScheduleOutputFactory
{
    public function buildOne(TrainingPlanningEntryDataModel|HabitPlanningEntryDataModel|TaskPlanningEntryDataModel $entry): PlanningScheduleDataOutput
    {
        $schedule = new PlanningScheduleDataOutput();
        $schedule->rule = $entry->rule;
        $schedule->startDate = (string) DateDataTransformer::dateToDayString($entry->startDate);
        $schedule->endDate = DateDataTransformer::dateToDayString($entry->endDate);
        $schedule->weekdays = $entry->weekdays;
        $schedule->weekInterval = $entry->weekInterval;
        $schedule->monthWeeks = $entry->monthWeeks;
        $schedule->monthDays = $entry->monthDays;
        $schedule->startTime = TimeDataTransformer::minutesToTimeString($entry->startTimeInMinutes);
        $schedule->durationInMinutes = $entry->durationInMinutes;

        return $schedule;
    }
}
