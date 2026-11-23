<?php

declare(strict_types=1);

namespace App\Tests;

use App\Domain\DTO\DataModel\Planning\TrainingPlanningEntryDataModel;
use App\Domain\DTO\DataModel\Training\RoutineDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Planning\PlanningScheduleDataInput;
use App\Domain\Registry\Planning\PlanningRuleRegistry;
use DateTimeImmutable;

/** Planning entries and schedules built by hand, the way a request or Doctrine would hand them over. */
trait PlanningEntryTrait
{
    /** @param list<int> $weekdays */
    private function weeklySchedule(array $weekdays = [2], string $startDate = '2026-11-02', ?string $startTime = null, ?int $duration = null): PlanningScheduleDataInput
    {
        return new PlanningScheduleDataInput(
            rule: PlanningRuleRegistry::WEEKLY,
            startDate: $startDate,
            weekdays: $weekdays,
            startTime: $startTime,
            durationInMinutes: $duration,
        );
    }

    /** @param list<int> $weekdays */
    private function trainingEntry(int $id, RoutineDataModel $routine, array $weekdays = [2], ?int $startTime = null, ?UserDataModel $owner = null): TrainingPlanningEntryDataModel
    {
        $entry = new TrainingPlanningEntryDataModel();
        $entry->id = $id;
        $entry->owner = $owner ?? $routine->owner ?? new UserDataModel();
        $entry->routine = $routine;
        $entry->rule = PlanningRuleRegistry::WEEKLY;
        $entry->startDate = new DateTimeImmutable('2026-11-02');
        $entry->weekdays = $weekdays;
        $entry->startTimeInMinutes = $startTime;

        return $entry;
    }
}
