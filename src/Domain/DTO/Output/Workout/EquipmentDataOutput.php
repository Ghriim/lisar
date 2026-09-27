<?php

declare(strict_types=1);

namespace App\Domain\DTO\Output\Workout;

/** An equipment, every field, active or retired. */
final class EquipmentDataOutput
{
    public int $id;

    public string $name;

    public bool $hasWeight;

    public bool $hasDistance;

    public bool $isActive;
}
