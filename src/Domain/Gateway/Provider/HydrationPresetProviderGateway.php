<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Provider;

use App\Domain\DTO\DataModel\HydrationPresetDataModel;

interface HydrationPresetProviderGateway
{
    public function findOneById(int $id): ?HydrationPresetDataModel;

    /**
     * Smallest volume first: that is the order they are offered in.
     *
     * @return list<HydrationPresetDataModel>
     */
    public function findAllOrderedByVolume(): array;
}
