<?php

declare(strict_types=1);

namespace App\Domain\DTO\Output\Planning;

/**
 * When a planning entry falls, as it was saved: only the fields its rule reads are filled, the
 * others empty. The same on every kind of entry.
 */
final class PlanningScheduleDataOutput
{
    /** One of once, weekly, monthly_weekday, monthly_day. */
    public string $rule;

    /** A calendar day, "YYYY-MM-DD". */
    public string $startDate;

    public ?string $endDate = null;

    /** @var list<int> ISO weekdays, 1 for Monday */
    public array $weekdays = [];

    public int $weekInterval = 1;

    /** @var list<int> 1 to 5, or -1 for the last */
    public array $monthWeeks = [];

    /** @var list<int> 1 to 31 */
    public array $monthDays = [];

    /** "HH:MM", or null for an entry within the day. */
    public ?string $startTime = null;

    public ?int $durationInMinutes = null;
}
