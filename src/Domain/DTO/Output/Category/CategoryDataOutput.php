<?php

declare(strict_types=1);

namespace App\Domain\DTO\Output\Category;

final class CategoryDataOutput
{
    public int $id;

    public string $label;

    /** False for a reference category, true for one the account created for itself. */
    public bool $isPersonal;

    /** One of CategoryIconRegistry's codes; null on a personal category. */
    public ?string $icon = null;
}
