<?php

declare(strict_types=1);

namespace App\Domain\DataTransformer;

use DateTimeImmutable;
use DateTimeInterface;

use function sprintf;

/**
 * The two date formats the API speaks, and there are no others. No endpoint invents its own.
 */
final readonly class DateDataTransformer
{
    /** A moment in time: ISO 8601 with offset. */
    public const string FORMAT = 'Y-m-d\TH:i:sP';

    /**
     * A calendar day. A due date has no time of day, and pretending it falls at midnight in
     * some timezone is how a deadline slips by a day.
     */
    public const string DAY_FORMAT = 'Y-m-d';

    public static function dateToString(?DateTimeInterface $date): ?string
    {
        return $date?->format(self::FORMAT);
    }

    public static function dateToDayString(?DateTimeInterface $date): ?string
    {
        return $date?->format(self::DAY_FORMAT);
    }

    /** Returns null on anything that is not a well-formed day, an empty string included. */
    public static function dayStringToDate(?string $day): ?DateTimeImmutable
    {
        if (null === $day || '' === $day) {
            return null;
        }

        // The trailing "|" resets the time fields, so the value is a day and not "today at
        // whatever o'clock".
        $date = DateTimeImmutable::createFromFormat(sprintf('%s|', self::DAY_FORMAT), $day);

        return false === $date ? null : $date;
    }
}
