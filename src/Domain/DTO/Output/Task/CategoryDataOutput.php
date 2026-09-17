<?php

declare(strict_types=1);

namespace App\Domain\DTO\Output\Task;

final class CategoryDataOutput
{
    public int $id;

    public string $label;

    /** False for a reference category, true for one the account created for itself. */
    public bool $isPersonal;
}
