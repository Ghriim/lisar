<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Persister\Tracking\Hydration;

use App\Domain\DTO\DataModel\Tracking\HydrationDayDataModel;

interface HydrationDayPersisterGateway
{
    public function create(HydrationDayDataModel $day): HydrationDayDataModel;

    public function update(HydrationDayDataModel $day): HydrationDayDataModel;
}
