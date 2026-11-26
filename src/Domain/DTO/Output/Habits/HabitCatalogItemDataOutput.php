<?php

declare(strict_types=1);

namespace App\Domain\DTO\Output\Habits;

use App\Domain\DTO\Output\Category\CategoryDataOutput;

/**
 * One catalogue habit in the subscribe window: what it is, and whether the person already keeps it.
 */
final class HabitCatalogItemDataOutput
{
    public int $habitId;

    public string $name;

    /** manual | tracker. */
    public string $sourceKind;

    /** The tracker that keeps it, or null for a manual habit. */
    public ?string $trackerKind = null;

    /** The mark that tracker must cross, or null for a manual habit. */
    public ?int $trackerThreshold = null;

    /** A common category, or null; set by the back-office on the catalogue habit. */
    public ?CategoryDataOutput $category = null;

    public bool $isSubscribed;
}
