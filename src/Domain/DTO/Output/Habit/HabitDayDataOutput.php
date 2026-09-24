<?php

declare(strict_types=1);

namespace App\Domain\DTO\Output\Habit;

/** One of the seven days a habit's line shows: the day, and whether it was kept. */
final class HabitDayDataOutput
{
    /** A calendar day, YYYY-MM-DD. */
    public string $day;

    public bool $isCompleted;
}
