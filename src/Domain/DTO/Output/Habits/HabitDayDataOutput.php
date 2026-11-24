<?php

declare(strict_types=1);

namespace App\Domain\DTO\Output\Habits;

/** One of the seven days a habit's line shows: the day, whether it was kept, and whether it counts. */
final class HabitDayDataOutput
{
    /** A calendar day, YYYY-MM-DD. */
    public string $day;

    public bool $isCompleted;

    /** Whether the habit's planning asks for the day: every day for a habit without any entry. */
    public bool $isPlanned;
}
