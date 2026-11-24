<?php

declare(strict_types=1);

namespace App\Domain\Planning;

use App\Domain\DTO\DataModel\Habits\HabitEntryDataModel;
use App\Domain\DTO\DataModel\Habits\HabitSubscriptionDataModel;
use App\Domain\DTO\DataModel\Planning\HabitPlanningEntryDataModel;
use App\Domain\Registry\Planning\PlanningOccurrenceStateRegistry;
use App\Domain\Tracking\DayClock;
use DateTimeImmutable;

use const PHP_INT_MAX;

/**
 * The days habit entries ask for, and where each stands. A habit without any entry counts every
 * day; once it has some, only the days they fall on are planned. A planned day is kept when the
 * habit was kept that day — by hand or by its tracker — to come until the day is over, then
 * missed. A habit kept on a day none of its entries asks for is kept unplanned: a bonus, outside
 * the planning. Nothing of this is stored: it is read from the habit's days each time.
 */
final readonly class HabitPlanningCalendar
{
    private const string DAY = 'Y-m-d';

    public function __construct(
        private PlanningRule $planningRule,
        private DayClock $clock,
    ) {
    }

    /**
     * @param list<HabitPlanningEntryDataModel> $entries   in the order the dates of one day are shown in
     * @param list<HabitEntryDataModel>         $habitDays the owner's habit days over those days, kept or not
     * @param DateTimeImmutable                 $from      the first day, at midnight in the zone days are counted in
     * @param DateTimeImmutable                 $to        the last day, likewise
     *
     * @return list<array{entry: HabitPlanningEntryDataModel|null, subscription: HabitSubscriptionDataModel, day: DateTimeImmutable, state: string}>
     *                                                                                                                                               by day, the planned dates in the order of `$entries`, then those kept unplanned
     */
    public function slotsBetween(array $entries, array $habitDays, DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $today = $this->clock->today()->format(self::DAY);

        $kept = [];
        foreach ($habitDays as $habitDay) {
            if (true === $habitDay->isCompleted) {
                $kept[$this->key($habitDay->subscription, $habitDay->day)] = $habitDay;
            }
        }

        $slots = [];
        $planned = [];
        $subscriptions = [];
        foreach ($entries as $order => $entry) {
            $subscriptions[(int) $entry->subscription->id] = $entry->subscription;
            foreach ($this->planningRule->datesOf($entry, $from, $to) as $day) {
                $key = $this->key($entry->subscription, $day);
                $planned[$key] = true;
                $slots[] = [
                    'entry' => $entry,
                    'subscription' => $entry->subscription,
                    'day' => $day,
                    'state' => match (true) {
                        isset($kept[$key]) => PlanningOccurrenceStateRegistry::KEPT,
                        $day->format(self::DAY) < $today => PlanningOccurrenceStateRegistry::MISSED,
                        default => PlanningOccurrenceStateRegistry::TO_COME,
                    },
                    'order' => $order,
                ];
            }
        }

        // Only a planned habit has unplanned days: one without entries counts every day.
        foreach ($kept as $key => $habitDay) {
            $subscription = $subscriptions[(int) $habitDay->subscription->id] ?? null;
            if (null === $subscription || true === isset($planned[$key])) {
                continue;
            }
            $slots[] = [
                'entry' => null,
                'subscription' => $subscription,
                'day' => $this->midnight($habitDay->day, $from),
                'state' => PlanningOccurrenceStateRegistry::KEPT_UNPLANNED,
                'order' => PHP_INT_MAX,
            ];
        }

        usort($slots, static fn (array $a, array $b): int => [$a['day']->format(self::DAY), $a['order'], $a['subscription']->id] <=> [$b['day']->format(self::DAY), $b['order'], $b['subscription']->id]);

        return array_map(static fn (array $slot): array => [
            'entry' => $slot['entry'],
            'subscription' => $slot['subscription'],
            'day' => $slot['day'],
            'state' => $slot['state'],
        ], $slots);
    }

    /**
     * Which days each planned habit counts over some days. A subscription absent from the answer
     * has no entry, and counts every day.
     *
     * @param list<HabitPlanningEntryDataModel> $entries
     *
     * @return array<int, array<string, true>> subscription id => (day "YYYY-MM-DD" => true)
     */
    public function plannedDaysBetween(array $entries, DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $planned = [];
        foreach ($entries as $entry) {
            $planned[(int) $entry->subscription->id] ??= [];
            foreach ($this->planningRule->datesOf($entry, $from, $to) as $day) {
                $planned[(int) $entry->subscription->id][$day->format(self::DAY)] = true;
            }
        }

        return $planned;
    }

    private function key(HabitSubscriptionDataModel $subscription, DateTimeImmutable $day): string
    {
        return $subscription->id.'@'.$day->format(self::DAY);
    }

    /** That calendar day at midnight, in the timezone of `$zoned`. */
    private function midnight(DateTimeImmutable $day, DateTimeImmutable $zoned): DateTimeImmutable
    {
        $midnight = DateTimeImmutable::createFromFormat('!'.self::DAY, $day->format(self::DAY), $zoned->getTimezone());

        return false === $midnight ? $zoned : $midnight;
    }
}
