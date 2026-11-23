<?php

declare(strict_types=1);

namespace App\Domain\Factory\OutputFactory\Planning;

use App\Domain\DataTransformer\DateDataTransformer;
use App\Domain\DataTransformer\TimeDataTransformer;
use App\Domain\DTO\DataModel\Planning\TrainingPlanningEntryDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\Output\Planning\PlanningScheduleDataOutput;
use App\Domain\DTO\Output\Planning\TrainingPlanningDataOutput;
use App\Domain\DTO\Output\Planning\TrainingPlanningEntryDataOutput;
use App\Domain\DTO\Output\Planning\TrainingPlanningSlotDataOutput;
use DateTimeImmutable;

final readonly class TrainingPlanningOutputFactory
{
    /**
     * @param list<TrainingPlanningEntryDataModel>                                                                                      $entries
     * @param list<array{entry: TrainingPlanningEntryDataModel, day: DateTimeImmutable, state: string, workout: WorkoutDataModel|null}> $slots
     */
    public function buildMany(array $entries, array $slots): TrainingPlanningDataOutput
    {
        $output = new TrainingPlanningDataOutput();
        $output->entries = array_map($this->buildOne(...), $entries);

        foreach ($slots as $slot) {
            $one = new TrainingPlanningSlotDataOutput();
            $one->entryId = (int) $slot['entry']->id;
            $one->day = (string) DateDataTransformer::dateToDayString($slot['day']);
            $one->state = $slot['state'];
            $one->workoutId = $slot['workout']?->id;
            $output->slots[] = $one;
        }

        return $output;
    }

    public function buildOne(TrainingPlanningEntryDataModel $entry): TrainingPlanningEntryDataOutput
    {
        $output = new TrainingPlanningEntryDataOutput();
        $output->id = (int) $entry->id;
        $output->routineId = (int) $entry->routine->id;
        $output->routineName = $entry->routine->name;

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
        $output->schedule = $schedule;

        return $output;
    }
}
