<?php

declare(strict_types=1);

namespace App\Domain\Tracking;

use DateTimeImmutable;
use DateTimeInterface;

use function intdiv;

/**
 * The two moments a night is, worked out from the two times a person gives and the day they woke
 * up on.
 *
 * This is where "I went to bed at 23:30 and got up at 07:00" becomes two instants. The rule is
 * the one thing that makes the pair unambiguous: **a bedtime later in the day than the wake-up
 * time is the evening before**. 23:30 → 07:00 is a night; 01:00 → 09:00 is the same night begun
 * after midnight.
 */
final readonly class SleepWindow
{
    /** A typo filter, not a judgement: nobody means the half-hour they typed by mistake. */
    public const int MINIMUM_DURATION_IN_MINUTES = 30;

    public const int MAXIMUM_DURATION_IN_MINUTES = 16 * 60;

    private const int MINUTES_PER_HOUR = 60;

    private const int SECONDS_PER_MINUTE = 60;

    private function __construct(
        public DateTimeImmutable $bedtimeAt,
        public DateTimeImmutable $wakeUpAt,
    ) {
    }

    /**
     * @param DateTimeImmutable $wakingDay        the day one got up on, with no time of day
     * @param int               $bedtimeInMinutes minutes since midnight
     * @param int               $wakeUpInMinutes  minutes since midnight
     */
    public static function forWakingDay(
        DateTimeImmutable $wakingDay,
        int $bedtimeInMinutes,
        int $wakeUpInMinutes,
    ): self {
        $bedtimeDay = $bedtimeInMinutes > $wakeUpInMinutes ? $wakingDay->modify('-1 day') : $wakingDay;

        return new self(self::at($bedtimeDay, $bedtimeInMinutes), self::at($wakingDay, $wakeUpInMinutes));
    }

    /**
     * How long the night lasted, in minutes — measured between the two instants, which is the
     * time that actually elapsed.
     *
     * Twice a year that is not what the clock on the wall says: on the night the clocks go
     * forward, 23:30 to 07:00 is six and a half hours of sleep, not seven and a half. The
     * elapsed time is the truth about the night, and it is the one definition used everywhere —
     * by the bounds that judge it and by the widget that shows it.
     */
    public static function durationInMinutes(DateTimeInterface $bedtimeAt, DateTimeInterface $wakeUpAt): int
    {
        return intdiv($wakeUpAt->getTimestamp() - $bedtimeAt->getTimestamp(), self::SECONDS_PER_MINUTE);
    }

    private static function at(DateTimeImmutable $day, int $minutesSinceMidnight): DateTimeImmutable
    {
        return $day->setTime(
            intdiv($minutesSinceMidnight, self::MINUTES_PER_HOUR),
            $minutesSinceMidnight % self::MINUTES_PER_HOUR,
        );
    }
}
