<?php

declare(strict_types=1);

namespace App\Domain\DTO\Output\Workout;

/** A movement family, active or retired. */
final class MovementFamilyDataOutput
{
    public int $id;

    public string $name;

    public bool $isActive;
}
