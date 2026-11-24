<?php

declare(strict_types=1);

namespace App\Domain\Planning;

use App\Domain\DTO\DataModel\Planning\TaskPlanningEntryDataModel;
use App\Domain\DTO\DataModel\Todo\TaskDataModel;
use App\Domain\Registry\Planning\PlanningOccurrenceStateRegistry;
use App\Domain\Registry\Planning\PlanningRuleRegistry;
use App\Domain\Tracking\DayClock;
use DateTimeImmutable;

use const PHP_INT_MAX;

/**
 * When a task entry makes its tasks, and where each of its dates stands.
 *
 * A recurring entry makes each date's task on the day itself, from midnight; a single date's
 * task is made at once, so it can be seen coming. A date whose task was made is open or done, as
 * the task is; a date still ahead with no task is to come. What already fell is read from the
 * tasks made, not from the rule: changing the rule leaves the dates gone by as they were.
 *
 * The dates to come are the entry's own: a task made ahead — a single date's — and not done yet
 * follows the entry when it changes, and goes when it is deleted. Today's task, and those before,
 * have fallen, and stay.
 */
final readonly class TaskPlanningCalendar
{
    private const string DAY = 'Y-m-d';

    public function __construct(
        private PlanningRule $planningRule,
        private DayClock $clock,
    ) {
    }

    /**
     * The dates whose task the entry makes now, as it is created or changed: a single date
     * whenever it falls, a recurring rule today only, if it falls today.
     *
     * @return list<DateTimeImmutable>
     */
    public function datesToMakeNow(TaskPlanningEntryDataModel $entry): array
    {
        $today = $this->clock->today();
        if (PlanningRuleRegistry::ONCE === $entry->rule) {
            return $this->planningRule->datesOf($entry, $entry->startDate, $entry->startDate);
        }

        return $this->planningRule->datesOf($entry, $today, $today);
    }

    /**
     * Whether the entry makes a task on that day, by its rule — what the daily run asks.
     */
    public function fallsOn(TaskPlanningEntryDataModel $entry, DateTimeImmutable $day): bool
    {
        return PlanningRuleRegistry::ONCE !== $entry->rule && [] !== $this->planningRule->datesOf($entry, $day, $day);
    }

    /** A task made ahead for a date still to come, and not done: it follows its entry. */
    public function isToCome(TaskDataModel $task): bool
    {
        return null !== $task->plannedDate
            && null === $task->completedAt
            && $task->plannedDate->format(self::DAY) > $this->clock->today()->format(self::DAY);
    }

    /**
     * @param list<TaskPlanningEntryDataModel> $entries in the order the dates of one day are shown in
     * @param list<TaskDataModel>              $tasks   the tasks the owner's entries made for those days
     * @param DateTimeImmutable                $from    the first day, at midnight in the zone days are counted in
     * @param DateTimeImmutable                $to      the last day, likewise
     *
     * @return list<array{entry: TaskPlanningEntryDataModel, day: DateTimeImmutable, state: string, task: TaskDataModel|null}>
     *                                                                                                                         by day, then in the order of `$entries`
     */
    public function slotsBetween(array $entries, array $tasks, DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $today = $this->clock->today()->format(self::DAY);

        $order = [];
        foreach ($entries as $rank => $entry) {
            $order[(int) $entry->id] = $rank;
        }

        $slots = [];
        $made = [];
        foreach ($tasks as $task) {
            $entry = $task->planningEntry;
            if (null === $entry || null === $task->plannedDate || false === isset($order[(int) $entry->id])) {
                continue;
            }
            $made[$entry->id.'@'.$task->plannedDate->format(self::DAY)] = true;
            $slots[] = [
                'entry' => $entry,
                'day' => $this->midnight($task->plannedDate, $from),
                'state' => null === $task->completedAt ? PlanningOccurrenceStateRegistry::OPEN : PlanningOccurrenceStateRegistry::DONE,
                'task' => $task,
                'order' => $order[(int) $entry->id],
                'taskId' => (int) $task->id,
            ];
        }

        foreach ($entries as $rank => $entry) {
            foreach ($this->planningRule->datesOf($entry, $from, $to) as $day) {
                $key = $entry->id.'@'.$day->format(self::DAY);
                if (true === isset($made[$key]) || $day->format(self::DAY) <= $today) {
                    continue;
                }
                $slots[] = [
                    'entry' => $entry,
                    'day' => $day,
                    'state' => PlanningOccurrenceStateRegistry::TO_COME,
                    'task' => null,
                    'order' => $rank,
                    'taskId' => PHP_INT_MAX,
                ];
            }
        }

        usort($slots, static fn (array $a, array $b): int => [$a['day']->format(self::DAY), $a['order'], $a['taskId']] <=> [$b['day']->format(self::DAY), $b['order'], $b['taskId']]);

        return array_map(static fn (array $slot): array => [
            'entry' => $slot['entry'],
            'day' => $slot['day'],
            'state' => $slot['state'],
            'task' => $slot['task'],
        ], $slots);
    }

    /** That calendar day at midnight, in the timezone of `$zoned`. */
    private function midnight(DateTimeImmutable $day, DateTimeImmutable $zoned): DateTimeImmutable
    {
        $midnight = DateTimeImmutable::createFromFormat('!'.self::DAY, $day->format(self::DAY), $zoned->getTimezone());

        return false === $midnight ? $zoned : $midnight;
    }
}
