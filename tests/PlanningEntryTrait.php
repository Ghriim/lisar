<?php

declare(strict_types=1);

namespace App\Tests;

use App\Domain\DTO\DataModel\Habits\HabitDataModel;
use App\Domain\DTO\DataModel\Habits\HabitEntryDataModel;
use App\Domain\DTO\DataModel\Habits\HabitSubscriptionDataModel;
use App\Domain\DTO\DataModel\Planning\HabitPlanningEntryDataModel;
use App\Domain\DTO\DataModel\Planning\TaskPlanningEntryDataModel;
use App\Domain\DTO\DataModel\Planning\TrainingPlanningEntryDataModel;
use App\Domain\DTO\DataModel\Todo\TaskDataModel;
use App\Domain\DTO\DataModel\Training\RoutineDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Planning\PlanningScheduleDataInput;
use App\Domain\Registry\Habits\HabitSourceRegistry;
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

    /** An active subscription of `$owner`'s to an active habit. */
    private function subscriptionOf(UserDataModel $owner, int $id = 1, int $habitId = 1): HabitSubscriptionDataModel
    {
        $habit = new HabitDataModel();
        $habit->id = $habitId;
        $habit->name = 'Lire';
        $habit->sourceKind = HabitSourceRegistry::MANUAL;

        $subscription = new HabitSubscriptionDataModel();
        $subscription->id = $id;
        $subscription->owner = $owner;
        $subscription->habit = $habit;

        return $subscription;
    }

    /** @param list<int> $weekdays */
    private function habitEntry(int $id, HabitSubscriptionDataModel $subscription, array $weekdays = [1]): HabitPlanningEntryDataModel
    {
        $entry = new HabitPlanningEntryDataModel();
        $entry->id = $id;
        $entry->owner = $subscription->owner;
        $entry->subscription = $subscription;
        $entry->rule = PlanningRuleRegistry::WEEKLY;
        $entry->startDate = new DateTimeImmutable('2026-11-02');
        $entry->weekdays = $weekdays;

        return $entry;
    }

    /** A day of the habit's, "YYYY-MM-DD", kept or not. */
    private function habitDay(HabitSubscriptionDataModel $subscription, string $day, bool $isCompleted = true): HabitEntryDataModel
    {
        $habitDay = new HabitEntryDataModel();
        $habitDay->subscription = $subscription;
        $habitDay->day = new DateTimeImmutable($day);
        $habitDay->isCompleted = $isCompleted;
        $habitDay->source = HabitSourceRegistry::MANUAL;

        return $habitDay;
    }

    /** @param list<int> $weekdays */
    private function taskEntry(int $id, UserDataModel $owner, string $rule = PlanningRuleRegistry::WEEKLY, array $weekdays = [1], string $startDate = '2026-11-02'): TaskPlanningEntryDataModel
    {
        $entry = new TaskPlanningEntryDataModel();
        $entry->id = $id;
        $entry->owner = $owner;
        $entry->title = 'Sortir les poubelles';
        $entry->rule = $rule;
        $entry->startDate = new DateTimeImmutable($startDate);
        $entry->weekdays = PlanningRuleRegistry::ONCE === $rule ? [] : $weekdays;

        return $entry;
    }

    /** The task an entry made for a day, "YYYY-MM-DD", done or not. */
    private function plannedTask(int $id, TaskPlanningEntryDataModel $entry, string $day, bool $isDone = false): TaskDataModel
    {
        $task = new TaskDataModel();
        $task->id = $id;
        $task->owner = $entry->owner;
        $task->title = $entry->title;
        $task->planningEntry = $entry;
        $task->plannedDate = new DateTimeImmutable($day);
        $task->dueDate = new DateTimeImmutable($day);
        $task->completedAt = true === $isDone ? new DateTimeImmutable('2026-11-20T10:00:00Z') : null;

        return $task;
    }
}
