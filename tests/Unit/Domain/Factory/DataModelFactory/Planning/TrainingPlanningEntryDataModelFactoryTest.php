<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Factory\DataModelFactory\Planning;

use App\Domain\DTO\Input\Planning\PlanningScheduleDataInput;
use App\Domain\Factory\DataModelFactory\Planning\PlanningEntryDataModelFactory;
use App\Domain\Factory\DataModelFactory\Planning\TrainingPlanningEntryDataModelFactory;
use App\Domain\Registry\Planning\PlanningRuleRegistry;
use App\Tests\CurrentUserTrait;
use App\Tests\RoutineTreeTrait;
use PHPUnit\Framework\TestCase;

final class TrainingPlanningEntryDataModelFactoryTest extends TestCase
{
    use CurrentUserTrait;
    use RoutineTreeTrait;

    public function testItKeepsWhatTheRuleReadsInOrder(): void
    {
        $owner = $this->anyCurrentUser();
        $routine = $this->routineOf($owner);

        $entry = (new TrainingPlanningEntryDataModelFactory(new PlanningEntryDataModelFactory()))->build($owner, $routine, new PlanningScheduleDataInput(
            rule: PlanningRuleRegistry::MONTHLY_WEEKDAY,
            startDate: '2026-11-02',
            endDate: '2027-02-28',
            weekdays: [5, 1, 1],
            weekInterval: 3,
            monthWeeks: [-1, 2, 1],
            monthDays: [15],
            startTime: '18:30',
            durationInMinutes: 75,
        ));

        self::assertSame($owner, $entry->owner);
        self::assertSame($routine, $entry->routine);
        self::assertSame('2026-11-02', $entry->startDate->format('Y-m-d'));
        self::assertSame('2027-02-28', $entry->endDate?->format('Y-m-d'));
        self::assertSame([1, 5], $entry->weekdays);
        self::assertSame([1, 2, -1], $entry->monthWeeks);
        // What a monthly weekday rule does not read is emptied.
        self::assertSame(1, $entry->weekInterval);
        self::assertSame([], $entry->monthDays);
        self::assertSame(18 * 60 + 30, $entry->startTimeInMinutes);
        self::assertSame(75, $entry->durationInMinutes);
    }

    public function testAOnceKeepsNoEndAndNoDays(): void
    {
        $owner = $this->anyCurrentUser();

        $entry = (new TrainingPlanningEntryDataModelFactory(new PlanningEntryDataModelFactory()))->build($owner, $this->routineOf($owner), new PlanningScheduleDataInput(
            rule: PlanningRuleRegistry::ONCE,
            startDate: '2026-11-28',
            endDate: '2026-12-31',
            weekdays: [1],
            durationInMinutes: 60,
        ));

        self::assertNull($entry->endDate);
        self::assertSame([], $entry->weekdays);
        // No start time: no duration either.
        self::assertNull($entry->startTimeInMinutes);
        self::assertNull($entry->durationInMinutes);
    }

    /** A change replaces the entry whole: what the new rule does not read goes. */
    public function testApplyingReplacesTheScheduleWhole(): void
    {
        $owner = $this->anyCurrentUser();
        $factory = new TrainingPlanningEntryDataModelFactory(new PlanningEntryDataModelFactory());
        $entry = $factory->build($owner, $this->routineOf($owner), new PlanningScheduleDataInput(PlanningRuleRegistry::WEEKLY, '2026-11-02', weekdays: [2], weekInterval: 2, startTime: '07:00'));

        $other = $this->routineOf($owner);
        $factory->apply($entry, $other, new PlanningScheduleDataInput(PlanningRuleRegistry::MONTHLY_DAY, '2026-12-01', monthDays: [31, 15]));

        self::assertSame($other, $entry->routine);
        self::assertSame(PlanningRuleRegistry::MONTHLY_DAY, $entry->rule);
        self::assertSame([], $entry->weekdays);
        self::assertSame(1, $entry->weekInterval);
        self::assertSame([15, 31], $entry->monthDays);
        self::assertNull($entry->startTimeInMinutes);
    }
}
