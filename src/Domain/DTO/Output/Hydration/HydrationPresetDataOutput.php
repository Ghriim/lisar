<?php

declare(strict_types=1);

namespace App\Domain\DTO\Output\Hydration;

final class HydrationPresetDataOutput
{
    public int $id;

    /** One of HydrationIconRegistry. Each front end draws it, and words it, its own way. */
    public string $icon;

    public int $volumeInMillilitres;
}
