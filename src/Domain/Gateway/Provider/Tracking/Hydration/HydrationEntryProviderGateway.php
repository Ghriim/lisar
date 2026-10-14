<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Provider\Tracking\Hydration;

use App\Domain\DTO\DataModel\Tracking\HydrationEntryDataModel;

interface HydrationEntryProviderGateway
{
    /** Whoever it belongs to: whether the caller may touch it is the validator's to say. */
    public function findOneById(int $id): ?HydrationEntryDataModel;
}
