<?php

declare(strict_types=1);

namespace App\Domain\DTO\Output\Training;

/** A set type, every field, active or retired. */
final class SetTypeDataOutput
{
    public int $id;

    public string $name;

    /** One of SetTypeColourRegistry's codes; each front end paints its own shade for it. */
    public string $colour;

    public bool $isActive;

    /** The one a set takes when it is logged without one. Exactly one type carries it. */
    public bool $isDefaultType;

    /** Whether its sets can set a personal best. */
    public bool $countsForPersonalBests;
}
