<?php

declare(strict_types=1);

namespace App\Domain\DTO\Output\Hydration;

/**
 * The day in progress: what was drunk, what the goal is, and everything logged so far.
 *
 * It is returned even for a day nothing was logged on — the widget has a goal to show and a
 * progress of zero, and reading must not write.
 */
final class HydrationDayDataOutput
{
    /** A calendar day, YYYY-MM-DD, in the timezone days are counted in. */
    public string $day;

    public int $goalInMillilitres;

    public int $totalInMillilitres;

    /** @var list<HydrationEntryDataOutput> */
    public array $entries = [];
}
