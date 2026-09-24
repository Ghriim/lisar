<?php

declare(strict_types=1);

namespace App\Domain\Registry\Habit;

/**
 * Who keeps a habit: the person, or a tracker on their behalf.
 *
 * `MANUAL` is a habit ticked by hand, on the widget. `TRACKER` is a habit kept on its own the
 * moment the tracker it watches crosses a mark. A `HabitEntry` records the same vocabulary in its
 * `source`, where a tracker-kept day carries the tracker's own name (see HabitTrackerRegistry)
 * rather than the bare `TRACKER`.
 */
interface HabitSourceRegistry
{
    /** Ticked by the person. */
    public const string MANUAL = 'manual';

    /** Kept by a tracker crossing its mark — see HabitTrackerRegistry for which. */
    public const string TRACKER = 'tracker';

    /** @var list<string> */
    public const array ALL = [self::MANUAL, self::TRACKER];
}
