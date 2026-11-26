<?php

declare(strict_types=1);

namespace App\Domain\DTO\Output\Habits;

use App\Domain\DTO\Output\Category\CategoryDataOutput;

/**
 * One subscribed habit, as its line on the panel: its name and its category, whose icon it wears;
 * whether it is the person's to tick, whether today is kept, and the last seven days to draw.
 */
final class HabitDataOutput
{
    /** The catalogue habit's id — what the complete / unsubscribe routes take. */
    public int $habitId;

    public string $name;

    /** manual | tracker — the front shows a tick button only for a manual habit. */
    public string $sourceKind;

    /** A common category, or null; set by the back-office on the catalogue habit. */
    public ?CategoryDataOutput $category = null;

    public bool $isCompletedToday;

    /**
     * The last seven days, oldest first, today last.
     *
     * @var list<HabitDayDataOutput>
     */
    public array $days;
}
