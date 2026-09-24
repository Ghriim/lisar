<?php

declare(strict_types=1);

namespace App\Domain\DTO\Output\Habit;

/** A catalogue habit as the back-office sees it: every field, active or retired. */
final class HabitAdminDataOutput
{
    public int $id;

    public string $name;

    public string $icon;

    /** manual | tracker. */
    public string $sourceKind;

    public ?string $trackerKind = null;

    public ?int $trackerThreshold = null;

    public bool $isActive;
}
