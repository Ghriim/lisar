<?php

declare(strict_types=1);

namespace App\Domain\DTO\Output\Task;

final class PriorityDataOutput
{
    public int $id;

    public string $label;

    public string $colour;

    /** Lower sorts first. The front ends sort on this, never on the label. */
    public int $weight;

    public bool $isDefault;
}
