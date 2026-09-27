<?php

declare(strict_types=1);

namespace App\Domain\DTO\Output\Workout;

/** A muscle group, active or retired. */
final class MuscleGroupDataOutput
{
    public int $id;

    public string $name;

    public bool $isActive;
}
