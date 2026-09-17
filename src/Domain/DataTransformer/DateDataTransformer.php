<?php

declare(strict_types=1);

namespace App\Domain\DataTransformer;

use DateTimeInterface;

/**
 * The one date format the API speaks. No endpoint invents its own.
 */
final readonly class DateDataTransformer
{
    /** ISO 8601 with offset. */
    public const string FORMAT = 'Y-m-d\TH:i:sP';

    public static function dateToString(?DateTimeInterface $date): ?string
    {
        return $date?->format(self::FORMAT);
    }
}
