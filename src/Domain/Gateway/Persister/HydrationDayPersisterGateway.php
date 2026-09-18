<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Persister;

use App\Domain\DTO\DataModel\HydrationDayDataModel;

interface HydrationDayPersisterGateway
{
    public function create(HydrationDayDataModel $day): HydrationDayDataModel;

    public function update(HydrationDayDataModel $day): HydrationDayDataModel;
}
