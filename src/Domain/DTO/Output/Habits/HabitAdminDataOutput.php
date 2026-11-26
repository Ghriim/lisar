<?php

declare(strict_types=1);

namespace App\Domain\DTO\Output\Habits;

use App\Domain\DTO\Output\Category\CategoryDataOutput;
use Symfony\Component\ObjectMapper\Attribute\Map;

/** A catalogue habit as the back-office sees it: every field, active or retired. */
final class HabitAdminDataOutput
{
    public int $id;

    public string $name;

    /** manual | tracker. */
    public string $sourceKind;

    public ?string $trackerKind = null;

    public ?int $trackerThreshold = null;

    /** A common category, or null; set by the back-office on the catalogue habit. */
    #[Map(if: false)]
    public ?CategoryDataOutput $category = null;

    public bool $isActive;
}
