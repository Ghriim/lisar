<?php

declare(strict_types=1);

namespace App\Domain\DTO\Output\Habit;

/**
 * One subscribed habit, as its line on the panel: its name and icon, whether it is the person's to
 * tick, whether today is kept, and the last seven days to draw.
 */
final class HabitDataOutput
{
    /** The catalogue habit's id — what the complete / unsubscribe routes take. */
    public int $habitId;

    public string $name;

    /** One of HabitIconRegistry's codes; each front end draws its own glyph for it. */
    public string $icon;

    /** manual | tracker — the front shows a tick button only for a manual habit. */
    public string $sourceKind;

    public bool $isCompletedToday;

    /**
     * The last seven days, oldest first, today last.
     *
     * @var list<HabitDayDataOutput>
     */
    public array $days;
}
