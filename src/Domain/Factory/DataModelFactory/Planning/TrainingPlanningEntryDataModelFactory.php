<?php

declare(strict_types=1);

namespace App\Domain\Factory\DataModelFactory\Planning;

use App\Domain\DataTransformer\DateDataTransformer;
use App\Domain\DataTransformer\TimeDataTransformer;
use App\Domain\DTO\DataModel\Planning\TrainingPlanningEntryDataModel;
use App\Domain\DTO\DataModel\Training\RoutineDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Planning\PlanningScheduleDataInput;
use App\Domain\Registry\Planning\PlanningRuleRegistry;
use DateTimeImmutable;

final readonly class TrainingPlanningEntryDataModelFactory
{
    /** A new entry of `$owner`'s, planning `$routine` on `$schedule`. */
    public function build(UserDataModel $owner, RoutineDataModel $routine, PlanningScheduleDataInput $schedule): TrainingPlanningEntryDataModel
    {
        $entry = new TrainingPlanningEntryDataModel();
        $entry->owner = $owner;
        $this->apply($entry, $routine, $schedule);

        return $entry;
    }

    /**
     * The entry replaced whole by what was sent. Only the fields its rule reads are kept: the
     * others are emptied, so that the row says what the rule is and nothing it ignores.
     */
    public function apply(TrainingPlanningEntryDataModel $entry, RoutineDataModel $routine, PlanningScheduleDataInput $schedule): void
    {
        $rule = $schedule->rule;
        $isWeekly = PlanningRuleRegistry::WEEKLY === $rule;
        $readsWeekdays = $isWeekly || PlanningRuleRegistry::MONTHLY_WEEKDAY === $rule;

        $entry->routine = $routine;
        $entry->rule = $rule;
        $entry->startDate = DateDataTransformer::dayStringToDate($schedule->startDate) ?? new DateTimeImmutable('@0');
        $entry->endDate = PlanningRuleRegistry::ONCE === $rule ? null : DateDataTransformer::dayStringToDate($schedule->endDate);
        $entry->weekdays = true === $readsWeekdays ? $this->sorted($schedule->weekdays) : [];
        $entry->weekInterval = true === $isWeekly ? $schedule->weekInterval : 1;
        $entry->monthWeeks = PlanningRuleRegistry::MONTHLY_WEEKDAY === $rule ? $this->sorted($schedule->monthWeeks) : [];
        $entry->monthDays = PlanningRuleRegistry::MONTHLY_DAY === $rule ? $this->sorted($schedule->monthDays) : [];
        $entry->startTimeInMinutes = TimeDataTransformer::timeStringToMinutes($schedule->startTime);
        $entry->durationInMinutes = null === $entry->startTimeInMinutes ? null : $schedule->durationInMinutes;
    }

    /**
     * Each value once, in order: [5, 1, 1] is Monday and Friday. The last week of the month sorts
     * after the others, where it reads.
     *
     * @param list<int> $values
     *
     * @return list<int>
     */
    private function sorted(array $values): array
    {
        $unique = array_values(array_unique($values));
        usort($unique, static fn (int $a, int $b): int => ($a < 0 ? 99 : $a) <=> ($b < 0 ? 99 : $b));

        return $unique;
    }
}
