<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Constraint\Planning;

use App\Domain\DTO\Input\Planning\PlanningScheduleDataInput;
use App\Domain\Registry\Planning\PlanningRuleRegistry;
use App\Domain\Validation\Constraint\Planning\PlanningScheduleConstraint;
use PHPUnit\Framework\TestCase;

final class PlanningScheduleConstraintTest extends TestCase
{
    public function testAScheduleThatHoldsIsAccepted(): void
    {
        self::assertSame([], PlanningScheduleConstraint::validate(new PlanningScheduleDataInput(PlanningRuleRegistry::WEEKLY, '2026-11-02', '2026-12-31', [1, 4], startTime: '18:00', durationInMinutes: 60), 'schedule'));
        self::assertSame([], PlanningScheduleConstraint::validate(new PlanningScheduleDataInput(PlanningRuleRegistry::ONCE, '2026-11-28'), 'schedule'));
    }

    public function testAWeeklyRuleNeedsItsDays(): void
    {
        self::assertSame(
            ['schedule.weekdays' => [PlanningScheduleConstraint::WEEKDAYS_REQUIRED]],
            PlanningScheduleConstraint::validate(new PlanningScheduleDataInput(PlanningRuleRegistry::WEEKLY, '2026-11-02'), 'schedule'),
        );
    }

    public function testAMonthlyWeekdayRuleNeedsItsDaysAndItsRanks(): void
    {
        self::assertSame(
            ['schedule.weekdays' => [PlanningScheduleConstraint::WEEKDAYS_REQUIRED], 'schedule.monthWeeks' => [PlanningScheduleConstraint::MONTH_WEEKS_REQUIRED]],
            PlanningScheduleConstraint::validate(new PlanningScheduleDataInput(PlanningRuleRegistry::MONTHLY_WEEKDAY, '2026-11-02'), 'schedule'),
        );
    }

    public function testAMonthlyDayRuleNeedsItsDays(): void
    {
        self::assertSame(
            ['schedule.monthDays' => [PlanningScheduleConstraint::MONTH_DAYS_REQUIRED]],
            PlanningScheduleConstraint::validate(new PlanningScheduleDataInput(PlanningRuleRegistry::MONTHLY_DAY, '2026-11-02', weekdays: [1]), 'schedule'),
        );
    }

    /** A once has no end to come before it: its end is ignored. */
    public function testAnEndBeforeTheStartIsRefusedButOnAOnce(): void
    {
        self::assertSame(
            ['schedule.endDate' => [PlanningScheduleConstraint::END_BEFORE_START]],
            PlanningScheduleConstraint::validate(new PlanningScheduleDataInput(PlanningRuleRegistry::WEEKLY, '2026-11-02', '2026-11-01', [1]), 'schedule'),
        );
        self::assertSame([], PlanningScheduleConstraint::validate(new PlanningScheduleDataInput(PlanningRuleRegistry::ONCE, '2026-11-02', '2026-11-01'), 'schedule'));
    }

    public function testADurationNeedsAStartTime(): void
    {
        self::assertSame(
            ['schedule.durationInMinutes' => [PlanningScheduleConstraint::DURATION_WITHOUT_START]],
            PlanningScheduleConstraint::validate(new PlanningScheduleDataInput(PlanningRuleRegistry::ONCE, '2026-11-02', durationInMinutes: 60), 'schedule'),
        );
    }

    public function testItAddsToTheViolationsAlreadyThere(): void
    {
        $violations = PlanningScheduleConstraint::validate(new PlanningScheduleDataInput(PlanningRuleRegistry::WEEKLY, '2026-11-02'), 'schedule', ['routineId' => ['routine_not_found']]);

        self::assertSame(['routine_not_found'], $violations['routineId']);
        self::assertArrayHasKey('schedule.weekdays', $violations);
    }
}
