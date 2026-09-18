<?php

declare(strict_types=1);

namespace App\Domain\DataTransformer;

use function preg_match;

/**
 * A time of day, with no date attached: "23:30".
 *
 * Kept apart from DateDataTransformer, which speaks of moments and of calendar days. A time of
 * day is neither: on its own it names no instant at all, and it takes a day to become one.
 */
final readonly class TimeDataTransformer
{
    /** Twenty-four hours, zero-padded. The only shape the API accepts for a time of day. */
    public const string PATTERN = '/^([01]\d|2[0-3]):[0-5]\d$/';

    private const int MINUTES_PER_HOUR = 60;

    /**
     * Minutes since midnight, or null on anything that is not a well-formed time — an empty
     * string included. Returning null rather than throwing is what lets a validator report a
     * malformed time as a violation instead of a failure.
     */
    public static function timeStringToMinutes(?string $time): ?int
    {
        if (null === $time || 1 !== preg_match(self::PATTERN, $time)) {
            return null;
        }

        [$hours, $minutes] = explode(':', $time);

        return ((int) $hours * self::MINUTES_PER_HOUR) + (int) $minutes;
    }
}
