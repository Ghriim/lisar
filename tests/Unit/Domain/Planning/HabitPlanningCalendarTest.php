<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Planning;

use App\Domain\Planning\HabitPlanningCalendar;
use App\Domain\Planning\PlanningRule;
use App\Domain\Registry\Planning\PlanningOccurrenceStateRegistry;
use App\Domain\Tracking\DayClock;
use App\Tests\CurrentUserTrait;
use App\Tests\PlanningEntryTrait;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

/** Today is Wednesday 25 November 2026, in Paris. The week runs from Monday 23 to Sunday 29. */
final class HabitPlanningCalendarTest extends TestCase
{
    use CurrentUserTrait;
    use PlanningEntryTrait;

    private HabitPlanningCalendar $calendar;

    protected function setUp(): void
    {
        parent::setUp();

        $clock = new DayClock(new MockClock(new DateTimeImmutable('2026-11-25T10:00:00Z')), 'Europe/Paris');
        $this->calendar = new HabitPlanningCalendar(new PlanningRule(), $clock);
    }

    public function testADateIsKeptMissedOrToCome(): void
    {
        $reading = $this->subscriptionOf($this->anyCurrentUser());
        // Reading on Monday, Tuesday, Wednesday and Thursday; kept on Tuesday only.
        $entry = $this->habitEntry(10, $reading, [1, 2, 3, 4]);

        self::assertSame(
            [
                ['2026-11-23', PlanningOccurrenceStateRegistry::MISSED, 10],
                ['2026-11-24', PlanningOccurrenceStateRegistry::KEPT, 10],
                // Today, not kept yet: still to come.
                ['2026-11-25', PlanningOccurrenceStateRegistry::TO_COME, 10],
                ['2026-11-26', PlanningOccurrenceStateRegistry::TO_COME, 10],
            ],
            $this->slots([$entry], [$this->habitDay($reading, '2026-11-24'), $this->habitDay($reading, '2026-11-23', false)]),
        );
    }

    /** Kept on a Saturday no entry asks for: a bonus, after the planned dates of its day. */
    public function testADayKeptUnplannedIsABonus(): void
    {
        $reading = $this->subscriptionOf($this->anyCurrentUser());
        $entry = $this->habitEntry(10, $reading, [6]);
        $other = $this->subscriptionOf($this->anyCurrentUser(), 2, 2);

        self::assertSame(
            [
                ['2026-11-24', PlanningOccurrenceStateRegistry::KEPT_UNPLANNED, null],
                ['2026-11-28', PlanningOccurrenceStateRegistry::TO_COME, 10],
            ],
            // The other habit has no entry: it counts every day, and stays off the planning.
            $this->slots([$entry], [$this->habitDay($reading, '2026-11-24'), $this->habitDay($other, '2026-11-24')]),
        );
    }

    public function testTheDatesOfOneDayFollowTheEntries(): void
    {
        $reading = $this->subscriptionOf($this->anyCurrentUser());
        $walk = $this->subscriptionOf($this->anyCurrentUser(), 2, 2);

        $slots = $this->slots([$this->habitEntry(20, $walk, [5]), $this->habitEntry(10, $reading, [5])], []);

        self::assertSame([20, 10], array_column($slots, 2));
    }

    public function testThePlannedDaysOfEachPlannedHabit(): void
    {
        $reading = $this->subscriptionOf($this->anyCurrentUser());
        $walk = $this->subscriptionOf($this->anyCurrentUser(), 2, 2);
        $zone = new DateTimeZone('Europe/Paris');

        $planned = $this->calendar->plannedDaysBetween(
            [$this->habitEntry(10, $reading, [1]), $this->habitEntry(11, $reading, [3]), $this->habitEntry(20, $walk, [7])],
            new DateTimeImmutable('2026-11-19', $zone),
            new DateTimeImmutable('2026-11-25', $zone),
        );

        // Walk's only day, a Sunday, is the 22nd: it is planned, on no day but that one.
        self::assertSame([1 => ['2026-11-23' => true, '2026-11-25' => true], 2 => ['2026-11-22' => true]], $planned);
    }

    public function testAHabitPlannedOnNoDayOfTheWindowIsStillPlanned(): void
    {
        $reading = $this->subscriptionOf($this->anyCurrentUser());
        $zone = new DateTimeZone('Europe/Paris');

        $entry = $this->habitEntry(10, $reading, [1]);
        $entry->startDate = new DateTimeImmutable('2027-01-01');

        self::assertSame([1 => []], $this->calendar->plannedDaysBetween([$entry], new DateTimeImmutable('2026-11-19', $zone), new DateTimeImmutable('2026-11-25', $zone)));
    }

    /**
     * @param list<\App\Domain\DTO\DataModel\Planning\HabitPlanningEntryDataModel> $entries
     * @param list<\App\Domain\DTO\DataModel\Habits\HabitEntryDataModel>           $habitDays
     *
     * @return list<array{0: string, 1: string, 2: int|null}>
     */
    private function slots(array $entries, array $habitDays): array
    {
        $zone = new DateTimeZone('Europe/Paris');

        return array_map(
            static fn (array $slot): array => [$slot['day']->format('Y-m-d'), $slot['state'], $slot['entry']?->id],
            $this->calendar->slotsBetween($entries, $habitDays, new DateTimeImmutable('2026-11-23', $zone), new DateTimeImmutable('2026-11-29', $zone)),
        );
    }
}
