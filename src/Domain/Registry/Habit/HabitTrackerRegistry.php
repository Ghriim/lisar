<?php

declare(strict_types=1);

namespace App\Domain\Registry\Habit;

/**
 * The trackers a habit can be fed by. A habit whose source is `TRACKER` names one of these, and is
 * kept the day that tracker's figure reaches the habit's mark.
 *
 * A fixed vocabulary in code, not reference data: it is the set of trackers the application ships,
 * and it grows when a tracker is added, not when an administrator decides. A tracker-kept
 * `HabitEntry` carries the matching value in its `source`.
 */
interface HabitTrackerRegistry
{
    /** The daily step count. */
    public const string STEPS = 'steps';

    /** The daily hydration total. */
    public const string HYDRATION = 'hydration';

    /** @var list<string> */
    public const array ALL = [self::STEPS, self::HYDRATION];
}
