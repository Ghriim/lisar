<?php

declare(strict_types=1);

namespace App\Domain\DTO\Output\Workout;

/** A set type, every field, active or retired. */
final class SetTypeDataOutput
{
    public int $id;

    public string $name;

    /** One of SetTypeColourRegistry's codes; each front end paints its own shade for it. */
    public string $colour;

    public bool $isActive;
}
