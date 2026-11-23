<?php

declare(strict_types=1);

namespace App\Domain\Validation\Constraint\Planning;

use App\Domain\DataTransformer\DateDataTransformer;
use App\Domain\DTO\Input\Planning\PlanningScheduleDataInput;
use App\Domain\Registry\Planning\PlanningRuleRegistry;

/**
 * What a schedule's rule needs, and how its fields hold together, once each has its own shape:
 * days of the week for a weekly rule, a rank in the month too for a monthly one, days of the month
 * for the other; an end that does not come before the start; a duration only after a start time.
 * The violations land under `$prefix.<field>`, where the schedule sits in the payload.
 */
final readonly class PlanningScheduleConstraint
{
    public const string WEEKDAYS_REQUIRED = 'weekdays_required';
    public const string MONTH_WEEKS_REQUIRED = 'month_weeks_required';
    public const string MONTH_DAYS_REQUIRED = 'month_days_required';
    public const string END_BEFORE_START = 'end_date_before_start';
    public const string DURATION_WITHOUT_START = 'duration_without_start_time';

    /**
     * @param array<string, list<string>> $violations
     *
     * @return array<string, list<string>>
     */
    public static function validate(PlanningScheduleDataInput $schedule, string $prefix, array $violations = []): array
    {
        $needsWeekdays = in_array($schedule->rule, [PlanningRuleRegistry::WEEKLY, PlanningRuleRegistry::MONTHLY_WEEKDAY], true);
        if (true === $needsWeekdays && [] === $schedule->weekdays) {
            $violations[$prefix.'.weekdays'][] = self::WEEKDAYS_REQUIRED;
        }
        if (PlanningRuleRegistry::MONTHLY_WEEKDAY === $schedule->rule && [] === $schedule->monthWeeks) {
            $violations[$prefix.'.monthWeeks'][] = self::MONTH_WEEKS_REQUIRED;
        }
        if (PlanningRuleRegistry::MONTHLY_DAY === $schedule->rule && [] === $schedule->monthDays) {
            $violations[$prefix.'.monthDays'][] = self::MONTH_DAYS_REQUIRED;
        }

        // A once has no end: its start is its only day.
        $start = DateDataTransformer::dayStringToDate($schedule->startDate);
        $end = DateDataTransformer::dayStringToDate($schedule->endDate);
        if (PlanningRuleRegistry::ONCE !== $schedule->rule && null !== $start && null !== $end && $end < $start) {
            $violations[$prefix.'.endDate'][] = self::END_BEFORE_START;
        }

        if (null !== $schedule->durationInMinutes && null === $schedule->startTime) {
            $violations[$prefix.'.durationInMinutes'][] = self::DURATION_WITHOUT_START;
        }

        return $violations;
    }
}
