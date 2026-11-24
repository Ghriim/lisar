<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Planning;

use App\Domain\DTO\DataModel\Planning\TaskPlanningEntryDataModel;
use App\Domain\DTO\DataModel\Todo\TaskDataModel;
use App\Domain\Planning\PlanningRule;
use App\Domain\Planning\TaskPlanningCalendar;
use App\Domain\Registry\Planning\PlanningOccurrenceStateRegistry;
use App\Domain\Registry\Planning\PlanningRuleRegistry;
use App\Domain\Tracking\DayClock;
use App\Tests\CurrentUserTrait;
use App\Tests\PlanningEntryTrait;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

/** Today is Wednesday 25 November 2026, in Paris. The week runs from Monday 23 to Sunday 29. */
final class TaskPlanningCalendarTest extends TestCase
{
    use CurrentUserTrait;
    use PlanningEntryTrait;

    private TaskPlanningCalendar $calendar;

    protected function setUp(): void
    {
        parent::setUp();

        $clock = new DayClock(new MockClock(new DateTimeImmutable('2026-11-25T10:00:00Z')), 'Europe/Paris');
        $this->calendar = new TaskPlanningCalendar(new PlanningRule(), $clock);
    }

    /** A recurring rule makes today's task now, if it falls today, and nothing ahead. */
    public function testARecurringEntryMakesTodaysTaskOnly(): void
    {
        self::assertSame(['2026-11-25'], $this->days($this->calendar->datesToMakeNow($this->taskEntry(1, $this->anyCurrentUser(), weekdays: [1, 3, 5]))));
        self::assertSame([], $this->calendar->datesToMakeNow($this->taskEntry(1, $this->anyCurrentUser(), weekdays: [1, 5])));
    }

    /** A single date's task is made at once, however far ahead — or behind. */
    public function testASingleDateMakesItsTaskAtOnce(): void
    {
        self::assertSame(['2026-12-24'], $this->days($this->calendar->datesToMakeNow($this->taskEntry(1, $this->anyCurrentUser(), PlanningRuleRegistry::ONCE, startDate: '2026-12-24'))));
        self::assertSame(['2026-11-20'], $this->days($this->calendar->datesToMakeNow($this->taskEntry(1, $this->anyCurrentUser(), PlanningRuleRegistry::ONCE, startDate: '2026-11-20'))));
    }

    /** The daily run reads recurring rules only: a single date's task was made with its entry. */
    public function testTheDailyRunFallsOnRecurringRulesOnly(): void
    {
        $monday = new DateTimeImmutable('2026-11-30');

        self::assertTrue($this->calendar->fallsOn($this->taskEntry(1, $this->anyCurrentUser(), weekdays: [1]), $monday));
        self::assertFalse($this->calendar->fallsOn($this->taskEntry(1, $this->anyCurrentUser(), weekdays: [2]), $monday));
        self::assertFalse($this->calendar->fallsOn($this->taskEntry(1, $this->anyCurrentUser(), PlanningRuleRegistry::ONCE, startDate: '2026-11-30'), $monday));
    }

    /** Only a task made ahead and not done follows its entry: today's and those before have fallen. */
    public function testATaskIsToComeWhenAheadAndNotDone(): void
    {
        $entry = $this->taskEntry(1, $this->anyCurrentUser());

        self::assertTrue($this->calendar->isToCome($this->plannedTask(1, $entry, '2026-11-26')));
        self::assertFalse($this->calendar->isToCome($this->plannedTask(2, $entry, '2026-11-26', true)));
        self::assertFalse($this->calendar->isToCome($this->plannedTask(3, $entry, '2026-11-25')));
        self::assertFalse($this->calendar->isToCome($this->plannedTask(4, $entry, '2026-11-20')));
    }

    /**
     * Every day of the week planned: Monday's task done, today's open, Tuesday's deleted by hand —
     * a past date with no task is not shown — and the days ahead to come.
     */
    public function testADateIsOpenDoneOrToCome(): void
    {
        $entry = $this->taskEntry(10, $this->anyCurrentUser(), weekdays: [1, 2, 3, 4, 5, 6, 7]);

        self::assertSame(
            [
                ['2026-11-23', PlanningOccurrenceStateRegistry::DONE, 1],
                ['2026-11-25', PlanningOccurrenceStateRegistry::OPEN, 2],
                ['2026-11-26', PlanningOccurrenceStateRegistry::TO_COME, null],
                ['2026-11-27', PlanningOccurrenceStateRegistry::TO_COME, null],
                ['2026-11-28', PlanningOccurrenceStateRegistry::TO_COME, null],
                ['2026-11-29', PlanningOccurrenceStateRegistry::TO_COME, null],
            ],
            $this->slots([$entry], [$this->plannedTask(1, $entry, '2026-11-23', true), $this->plannedTask(2, $entry, '2026-11-25')]),
        );
    }

    /** What fell is read from the tasks, not the rule: the rule changed to Fridays, Monday's task stays. */
    public function testATaskOfADateTheRuleNoLongerGivesStays(): void
    {
        $entry = $this->taskEntry(10, $this->anyCurrentUser(), weekdays: [5]);

        self::assertSame(
            [
                ['2026-11-23', PlanningOccurrenceStateRegistry::OPEN, 1],
                ['2026-11-27', PlanningOccurrenceStateRegistry::TO_COME, null],
            ],
            $this->slots([$entry], [$this->plannedTask(1, $entry, '2026-11-23')]),
        );
    }

    /** A single date ahead has its task already: open, not to come. */
    public function testASingleDateAheadIsOpen(): void
    {
        $entry = $this->taskEntry(10, $this->anyCurrentUser(), PlanningRuleRegistry::ONCE, startDate: '2026-11-28');

        self::assertSame([['2026-11-28', PlanningOccurrenceStateRegistry::OPEN, 1]], $this->slots([$entry], [$this->plannedTask(1, $entry, '2026-11-28')]));
    }

    /** Within a day, in the order of the entries; a task of an entry not listed is left out. */
    public function testDatesOfOneDayFollowTheEntries(): void
    {
        $evening = $this->taskEntry(10, $this->anyCurrentUser(), weekdays: [4]);
        $morning = $this->taskEntry(20, $this->anyCurrentUser(), weekdays: [4]);
        $gone = $this->taskEntry(30, $this->anyCurrentUser(), weekdays: [4]);

        $slots = $this->calendar->slotsBetween([$morning, $evening], [$this->plannedTask(1, $gone, '2026-11-23')], $this->day('2026-11-23'), $this->day('2026-11-29'));

        self::assertSame([20, 10], array_map(static fn (array $slot): ?int => $slot['entry']->id, $slots));
    }

    /**
     * @param list<TaskPlanningEntryDataModel> $entries
     * @param list<TaskDataModel>              $tasks
     *
     * @return list<array{string, string, int|null}>
     */
    private function slots(array $entries, array $tasks): array
    {
        return array_map(
            static fn (array $slot): array => [$slot['day']->format('Y-m-d'), $slot['state'], $slot['task']?->id],
            $this->calendar->slotsBetween($entries, $tasks, $this->day('2026-11-23'), $this->day('2026-11-29')),
        );
    }

    /**
     * @param list<DateTimeImmutable> $days
     *
     * @return list<string>
     */
    private function days(array $days): array
    {
        return array_map(static fn (DateTimeImmutable $day): string => $day->format('Y-m-d'), $days);
    }

    private function day(string $day): DateTimeImmutable
    {
        return new DateTimeImmutable($day, new DateTimeZone('Europe/Paris'));
    }
}
