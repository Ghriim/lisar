<?php

declare(strict_types=1);

namespace App\Domain\DTO\Output\Sleep;

/**
 * The night of the day in progress.
 *
 * Every field but the day is null when nothing has been noted this morning — which is the normal
 * first state of every day, not an error. The day itself is always there: it is the day being
 * written to, and it is how a page left open overnight notices that it is now looking at
 * yesterday.
 */
final class SleepNightDataOutput
{
    /** The waking day, YYYY-MM-DD, in the timezone days are counted in. */
    public string $day;

    /** The instant one went to bed — the evening before, when the night crossed midnight. */
    public ?string $bedtimeAt = null;

    public ?string $wakeUpAt = null;

    /** Elapsed between the two, which is what the night lasted. */
    public ?int $durationInMinutes = null;

    /** How one felt on waking, 1 to 5, or null for a night one did not rate. */
    public ?int $moodRating = null;
}
