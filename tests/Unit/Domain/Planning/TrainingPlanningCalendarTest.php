<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Planning;

use App\Domain\DTO\DataModel\Training\RoutineDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\Planning\PlanningRule;
use App\Domain\Planning\TrainingPlanningCalendar;
use App\Domain\Registry\Planning\PlanningOccurrenceStateRegistry;
use App\Domain\Tracking\DayClock;
use App\Tests\CurrentUserTrait;
use App\Tests\PlanningEntryTrait;
use App\Tests\RoutineTreeTrait;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

/** Today is Wednesday 25 November 2026, in Paris. The week runs from Monday 23 to Sunday 29. */
final class TrainingPlanningCalendarTest extends TestCase
{
    use CurrentUserTrait;
    use PlanningEntryTrait;
    use RoutineTreeTrait;

    private TrainingPlanningCalendar $calendar;
    private RoutineDataModel $push;
    private RoutineDataModel $legs;

    protected function setUp(): void
    {
        parent::setUp();

        $clock = new DayClock(new MockClock(new DateTimeImmutable('2026-11-25T10:00:00Z')), 'Europe/Paris');
        $this->calendar = new TrainingPlanningCalendar(new PlanningRule(), $clock);
        $this->push = $this->routineOf($this->anyCurrentUser());
        $this->push->id = 1;
        $this->legs = $this->routineOf($this->anyCurrentUser());
        $this->legs->id = 2;
    }

    public function testASlotIsKeptMissedOrToCome(): void
    {
        // Push on Monday, Tuesday, Wednesday and Thursday; done on Tuesday only.
        $entry = $this->trainingEntry(10, $this->push, [1, 2, 3, 4]);

        $slots = $this->slots([$entry], [$this->workout(100, $this->push, '2026-11-24T17:00:00Z')]);

        self::assertSame(
            [
                ['2026-11-23', PlanningOccurrenceStateRegistry::MISSED, null],
                ['2026-11-24', PlanningOccurrenceStateRegistry::KEPT, 100],
                // Today, not done yet: still to come.
                ['2026-11-25', PlanningOccurrenceStateRegistry::TO_COME, null],
                ['2026-11-26', PlanningOccurrenceStateRegistry::TO_COME, null],
            ],
            $slots,
        );
    }

    /** Only a workout of that routine keeps it: a Legs workout on a Push day keeps nothing. */
    public function testOnlyAWorkoutOfItsRoutineKeepsASlot(): void
    {
        $slots = $this->slots([$this->trainingEntry(10, $this->push, [2])], [$this->workout(100, $this->legs, '2026-11-24T17:00:00Z')]);

        self::assertSame([['2026-11-24', PlanningOccurrenceStateRegistry::MISSED, null]], $slots);
    }

    /** One workout keeps one slot: two Push slots on Tuesday and one workout keep the first alone. */
    public function testOneWorkoutKeepsOneSlot(): void
    {
        $morning = $this->trainingEntry(10, $this->push, [2], startTime: 7 * 60);
        $evening = $this->trainingEntry(11, $this->push, [2], startTime: 18 * 60);

        $slots = $this->slots([$morning, $evening], [$this->workout(100, $this->push, '2026-11-24T17:00:00Z')]);

        self::assertSame(
            [
                ['2026-11-24', PlanningOccurrenceStateRegistry::KEPT, 100],
                ['2026-11-24', PlanningOccurrenceStateRegistry::MISSED, null],
            ],
            $slots,
        );
    }

    /** A workout started at 00:30 in Paris is on that Paris day, though it is the day before in UTC. */
    public function testTheDayOfAWorkoutIsReadInParis(): void
    {
        $slots = $this->slots([$this->trainingEntry(10, $this->push, [2])], [$this->workout(100, $this->push, '2026-11-23T23:30:00Z')]);

        self::assertSame([['2026-11-24', PlanningOccurrenceStateRegistry::KEPT, 100]], $slots);
    }

    public function testSlotsComeByDayThenInTheOrderOfTheEntries(): void
    {
        $slots = $this->calendar->slotsBetween(
            [$this->trainingEntry(10, $this->push, [1, 3]), $this->trainingEntry(11, $this->legs, [1])],
            [],
            $this->day('2026-11-23'),
            $this->day('2026-11-29'),
        );

        self::assertSame(
            [['2026-11-23', 10], ['2026-11-23', 11], ['2026-11-25', 10]],
            array_map(static fn (array $slot) => [$slot['day']->format('Y-m-d'), $slot['entry']->id], $slots),
        );
    }

    /**
     * @param list<\App\Domain\DTO\DataModel\Planning\TrainingPlanningEntryDataModel> $entries
     * @param list<WorkoutDataModel>                                                  $workouts
     *
     * @return list<array{string, string, int|null}>
     */
    private function slots(array $entries, array $workouts): array
    {
        return array_map(
            static fn (array $slot) => [$slot['day']->format('Y-m-d'), $slot['state'], $slot['workout']?->id],
            $this->calendar->slotsBetween($entries, $workouts, $this->day('2026-11-23'), $this->day('2026-11-29')),
        );
    }

    private function workout(int $id, RoutineDataModel $routine, string $startedAt): WorkoutDataModel
    {
        $workout = new WorkoutDataModel();
        $workout->id = $id;
        $workout->routine = $routine;
        $workout->startedAt = new DateTimeImmutable($startedAt);

        return $workout;
    }

    private function day(string $day): DateTimeImmutable
    {
        return new DateTimeImmutable($day, new DateTimeZone('Europe/Paris'));
    }
}
