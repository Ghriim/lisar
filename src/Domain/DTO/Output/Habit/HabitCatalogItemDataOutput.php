<?php

declare(strict_types=1);

namespace App\Domain\DTO\Output\Habit;

/**
 * One catalogue habit in the subscribe window: what it is, and whether the person already keeps it.
 */
final class HabitCatalogItemDataOutput
{
    public int $habitId;

    public string $name;

    public string $icon;

    /** manual | tracker. */
    public string $sourceKind;

    /** The tracker that keeps it, or null for a manual habit. */
    public ?string $trackerKind = null;

    /** The mark that tracker must cross, or null for a manual habit. */
    public ?int $trackerThreshold = null;

    public bool $isSubscribed;
}
