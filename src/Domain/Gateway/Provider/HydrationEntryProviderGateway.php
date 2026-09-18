<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Provider;

use App\Domain\DTO\DataModel\HydrationEntryDataModel;
use App\Domain\DTO\DataModel\UserDataModel;

interface HydrationEntryProviderGateway
{
    /** Scoped to the owner: someone else's entry is simply not found. */
    public function findOneByIdForOwner(int $id, UserDataModel $owner): ?HydrationEntryDataModel;
}
