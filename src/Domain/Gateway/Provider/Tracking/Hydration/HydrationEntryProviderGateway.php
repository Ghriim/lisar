<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Provider\Tracking\Hydration;

use App\Domain\DTO\DataModel\Tracking\HydrationEntryDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;

interface HydrationEntryProviderGateway
{
    /** Scoped to the owner: someone else's entry is simply not found. */
    public function findOneByIdForOwner(int $id, UserDataModel $owner): ?HydrationEntryDataModel;
}
