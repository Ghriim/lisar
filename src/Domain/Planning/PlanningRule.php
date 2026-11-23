<?php

declare(strict_types=1);

namespace App\Domain\Planning;

use App\Domain\Registry\Planning\PlanningRuleRegistry;
use DateTimeImmutable;

use function in_array;

/**
 * The days a planning entry's schedule falls on. One engine for the three kinds of entry: each
 * carries the same fields, and hands them here.
 *
 * Days are calendar days, read without their time: what is given is compared by its `Y-m-d`, and
 * what comes back is each day at midnight, in the timezone `$from` is in. Weekdays are ISO —
 * 1 for Monday to 7 for Sunday.
 *
 * Which fields each rule reads:
 *
 * - `once`: the start date alone;
 * - `weekly`: `$weekdays`, every `$weekInterval` weeks counted from the start date's week —
 *   1 is every week;
 * - `monthly_weekday`: `$weekdays` × `$monthWeeks` — 1 to 5 for the first to the fifth, or
 *   `LAST_WEEK`;
 * - `monthly_day`: `$monthDays`, 1 to 31; a month without the day is skipped, not moved.
 *
 * A rule never falls before its start date nor after its end date. Values out of their range
 * simply never match: refusing them is the validators' business, not this one's.
 */
final readonly class PlanningRule
{
    private const string DAY = 'Y-m-d';

    /**
     * @param list<int> $weekdays   ISO weekdays
     * @param list<int> $monthWeeks 1 to 5, or LAST_WEEK
     * @param list<int> $monthDays  1 to 31
     *
     * @return list<DateTimeImmutable> every day from `$from` to `$to`, both included, the rule falls on, in order
     */
    public function datesBetween(
        string $rule,
        DateTimeImmutable $startDate,
        ?DateTimeImmutable $endDate,
        array $weekdays,
        int $weekInterval,
        array $monthWeeks,
        array $monthDays,
        DateTimeImmutable $from,
        DateTimeImmutable $to,
    ): array {
        $first = max($from->format(self::DAY), $startDate->format(self::DAY));
        $last = null === $endDate ? $to->format(self::DAY) : min($to->format(self::DAY), $endDate->format(self::DAY));
        if ($first > $last) {
            return [];
        }

        $start = $this->midnight($startDate->format(self::DAY), $from);
        $dates = [];
        for ($day = $this->midnight($first, $from); $day->format(self::DAY) <= $last; $day = $day->modify('+1 day')) {
            if (true === $this->fallsOn($rule, $start, $weekdays, $weekInterval, $monthWeeks, $monthDays, $day)) {
                $dates[] = $day;
            }
        }

        return $dates;
    }

    /** That calendar day at midnight, in the timezone of `$zoned`. */
    private function midnight(string $day, DateTimeImmutable $zoned): DateTimeImmutable
    {
        $midnight = DateTimeImmutable::createFromFormat('!'.self::DAY, $day, $zoned->getTimezone());

        return false === $midnight ? $zoned : $midnight;
    }

    /**
     * @param list<int> $weekdays
     * @param list<int> $monthWeeks
     * @param list<int> $monthDays
     */
    private function fallsOn(
        string $rule,
        DateTimeImmutable $start,
        array $weekdays,
        int $weekInterval,
        array $monthWeeks,
        array $monthDays,
        DateTimeImmutable $day,
    ): bool {
        return match ($rule) {
            PlanningRuleRegistry::ONCE => $day->format(self::DAY) === $start->format(self::DAY),
            PlanningRuleRegistry::WEEKLY => $this->isWeekday($day, $weekdays) && $this->isInWeekCycle($start, $day, $weekInterval),
            PlanningRuleRegistry::MONTHLY_WEEKDAY => $this->isWeekday($day, $weekdays) && $this->isInMonthWeeks($day, $monthWeeks),
            PlanningRuleRegistry::MONTHLY_DAY => in_array((int) $day->format('j'), $monthDays, true),
            default => false,
        };
    }

    /** @param list<int> $weekdays */
    private function isWeekday(DateTimeImmutable $day, array $weekdays): bool
    {
        return in_array((int) $day->format('N'), $weekdays, true);
    }

    /** Every N weeks, the start date's week being the first: weeks run Monday to Sunday. */
    private function isInWeekCycle(DateTimeImmutable $start, DateTimeImmutable $day, int $weekInterval): bool
    {
        if ($weekInterval < 1) {
            return false;
        }

        $weeks = intdiv((int) $this->mondayOf($start)->diff($this->mondayOf($day))->days, 7);

        return 0 === $weeks % $weekInterval;
    }

    private function mondayOf(DateTimeImmutable $day): DateTimeImmutable
    {
        return $day->modify(sprintf('-%d days', (int) $day->format('N') - 1));
    }

    /**
     * Which of its month's occurrences of its weekday the day is: the first seven days hold the
     * first, the next seven the second; the last is the one with no other a week later.
     *
     * @param list<int> $monthWeeks
     */
    private function isInMonthWeeks(DateTimeImmutable $day, array $monthWeeks): bool
    {
        $rank = intdiv((int) $day->format('j') - 1, 7) + 1;
        $isLast = (int) $day->format('j') + 7 > (int) $day->format('t');

        return in_array($rank, $monthWeeks, true) || (true === $isLast && in_array(PlanningRuleRegistry::LAST_WEEK, $monthWeeks, true));
    }
}
