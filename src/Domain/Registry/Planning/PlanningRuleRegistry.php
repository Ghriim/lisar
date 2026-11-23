<?php

declare(strict_types=1);

namespace App\Domain\Registry\Planning;

/**
 * The kinds of rule a planning entry falls by. Each reads its own fields of the schedule and
 * ignores the others — see PlanningRule.
 */
interface PlanningRuleRegistry
{
    /** The start date, and no other. */
    public const string ONCE = 'once';

    /** Some days of the week, every week or every N weeks counted from the start date's week. */
    public const string WEEKLY = 'weekly';

    /** The N-th given weekday of each month: the 1st and 2nd Monday, the last Friday. */
    public const string MONTHLY_WEEKDAY = 'monthly_weekday';

    /** Given days of each month: the 15th. A month without that day is skipped. */
    public const string MONTHLY_DAY = 'monthly_day';

    public const array ALL = [self::ONCE, self::WEEKLY, self::MONTHLY_WEEKDAY, self::MONTHLY_DAY];

    /** In `monthWeeks`, the last occurrence of the weekday in its month, however many it has. */
    public const int LAST_WEEK = -1;
}
