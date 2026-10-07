<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister\Tracking\Hydration;

use App\Domain\DTO\DataModel\Tracking\HydrationDayDataModel;
use App\Domain\Gateway\Persister\Tracking\Hydration\HydrationDayPersisterGateway;
use App\Infrastructure\Persister\AbstractBaseMysqlPersister;

/**
 * @extends AbstractBaseMysqlPersister<HydrationDayDataModel>
 */
final class HydrationDayPersister extends AbstractBaseMysqlPersister implements HydrationDayPersisterGateway
{
    public function create(HydrationDayDataModel $day): HydrationDayDataModel
    {
        return $this->persistAndStampCreate($day);
    }

    public function update(HydrationDayDataModel $day): HydrationDayDataModel
    {
        return $this->persistAndStampUpdate($day);
    }
}
