<?php

declare(strict_types=1);

namespace App\Domain\DTO\Output\Weight;

/**
 * The last known weight: today's if there is one, otherwise the most recent day's.
 *
 * Every field is null for an account that has never recorded one — the widget then has nothing
 * to show, and says so. It is the one output whose emptiness is the normal first state rather
 * than an error.
 */
final class WeightDataOutput
{
    public ?float $weightInKilograms = null;

    /** The calendar day the measurement belongs to, YYYY-MM-DD. */
    public ?string $day = null;

    /** The instant the person stepped on the scale, on the clock days are counted on. */
    public ?string $recordedAt = null;

    /**
     * Whether that weight is today's — which is the front end's only way to know whether
     * recording will create a measurement or correct the one already there. The browser cannot
     * work it out: the timezone days are counted in is a back-end decision.
     */
    public bool $isFromToday = false;
}
