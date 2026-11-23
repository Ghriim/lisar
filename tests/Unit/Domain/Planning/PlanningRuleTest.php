<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Planning;

use App\Domain\Planning\PlanningRule;
use App\Domain\Registry\Planning\PlanningRuleRegistry;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

/** November 2026 starts on a Sunday: Monday 2, 9, 16, 23 and 30. */
final class PlanningRuleTest extends TestCase
{
    private const string ZONE = 'Europe/Paris';

    public function testOnceFallsOnItsStartDateAlone(): void
    {
        self::assertSame(['2026-11-28'], $this->dates(PlanningRuleRegistry::ONCE, '2026-11-28', null, from: '2026-11-01', to: '2026-12-31'));
        self::assertSame([], $this->dates(PlanningRuleRegistry::ONCE, '2026-11-28', null, from: '2026-12-01', to: '2026-12-31'));
    }

    public function testWeeklyFallsOnItsDaysEveryWeek(): void
    {
        self::assertSame(
            ['2026-11-23', '2026-11-25', '2026-11-27'],
            $this->dates(PlanningRuleRegistry::WEEKLY, '2026-11-01', null, weekdays: [1, 3, 5], from: '2026-11-23', to: '2026-11-29'),
        );
    }

    public function testEveryDayIsTheSevenOfThem(): void
    {
        self::assertCount(7, $this->dates(PlanningRuleRegistry::WEEKLY, '2026-11-01', null, weekdays: [1, 2, 3, 4, 5, 6, 7], from: '2026-11-23', to: '2026-11-29'));
    }

    /**
     * Counted from the start date's week, Monday to Sunday: a Thursday the 12th starts the cycle on
     * the week of Monday the 9th — before the start, so skipped — and the next Monday it falls on is
     * two weeks on.
     */
    public function testEveryOtherWeekCountsFromTheStartDatesWeek(): void
    {
        self::assertSame(
            ['2026-11-23', '2026-12-07'],
            $this->dates(PlanningRuleRegistry::WEEKLY, '2026-11-12', null, weekdays: [1], weekInterval: 2, from: '2026-11-01', to: '2026-12-20'),
        );
    }

    public function testTheNthWeekdayOfTheMonth(): void
    {
        self::assertSame(
            ['2026-11-02', '2026-11-09', '2026-12-07', '2026-12-14'],
            $this->dates(PlanningRuleRegistry::MONTHLY_WEEKDAY, '2026-11-01', null, weekdays: [1], monthWeeks: [1, 2], from: '2026-11-01', to: '2026-12-31'),
        );
    }

    /** November has five Mondays, December four: the last is whichever has no other after it. */
    public function testTheLastWeekdayOfTheMonth(): void
    {
        self::assertSame(
            ['2026-11-30', '2026-12-28'],
            $this->dates(PlanningRuleRegistry::MONTHLY_WEEKDAY, '2026-11-01', null, weekdays: [1], monthWeeks: [PlanningRuleRegistry::LAST_WEEK], from: '2026-11-01', to: '2026-12-31'),
        );
    }

    public function testAFixedDayOfTheMonthSkipsAMonthWithoutIt(): void
    {
        self::assertSame(
            ['2026-12-31', '2027-01-31', '2027-03-31'],
            $this->dates(PlanningRuleRegistry::MONTHLY_DAY, '2026-12-01', null, monthDays: [31], from: '2026-12-01', to: '2027-04-15'),
        );
    }

    public function testARuleNeverFallsBeforeItsStartNorAfterItsEnd(): void
    {
        self::assertSame(
            ['2026-11-18', '2026-11-25'],
            $this->dates(PlanningRuleRegistry::WEEKLY, '2026-11-17', '2026-11-30', weekdays: [3], from: '2026-11-01', to: '2026-12-31'),
        );
        self::assertSame([], $this->dates(PlanningRuleRegistry::WEEKLY, '2026-12-01', null, weekdays: [3], from: '2026-11-01', to: '2026-11-30'));
    }

    /** What comes back is each day at midnight, in the zone the range was asked in, whatever time it was given with. */
    public function testDaysComeBackAtMidnightInTheRangesZone(): void
    {
        $zone = new DateTimeZone(self::ZONE);
        $dates = (new PlanningRule())->datesBetween(
            PlanningRuleRegistry::WEEKLY,
            new DateTimeImmutable('2026-10-01 18:30', $zone),
            null,
            [7],
            1,
            [],
            [],
            new DateTimeImmutable('2026-10-23 15:00', $zone),
            new DateTimeImmutable('2026-10-31 09:00', $zone),
        );

        // The 25th is when Paris leaves summer time: midnight still.
        self::assertSame(['2026-10-25T00:00:00+02:00'], array_map(static fn (DateTimeImmutable $day) => $day->format('c'), $dates));
    }

    public function testAnUnknownRuleOrAZeroIntervalNeverFalls(): void
    {
        self::assertSame([], $this->dates('fortnightly', '2026-11-01', null, weekdays: [1], from: '2026-11-01', to: '2026-11-30'));
        self::assertSame([], $this->dates(PlanningRuleRegistry::WEEKLY, '2026-11-01', null, weekdays: [1], weekInterval: 0, from: '2026-11-01', to: '2026-11-30'));
    }

    /**
     * @param list<int> $weekdays
     * @param list<int> $monthWeeks
     * @param list<int> $monthDays
     *
     * @return list<string>
     */
    private function dates(
        string $rule,
        string $start,
        ?string $end,
        array $weekdays = [],
        int $weekInterval = 1,
        array $monthWeeks = [],
        array $monthDays = [],
        string $from = '2026-11-01',
        string $to = '2026-11-30',
    ): array {
        $zone = new DateTimeZone(self::ZONE);
        $dates = (new PlanningRule())->datesBetween(
            $rule,
            new DateTimeImmutable($start, $zone),
            null === $end ? null : new DateTimeImmutable($end, $zone),
            $weekdays,
            $weekInterval,
            $monthWeeks,
            $monthDays,
            new DateTimeImmutable($from, $zone),
            new DateTimeImmutable($to, $zone),
        );

        return array_map(static fn (DateTimeImmutable $day) => $day->format('Y-m-d'), $dates);
    }
}
