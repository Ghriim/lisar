<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister;

use App\Domain\DTO\DataModel\HydrationDayDataModel;
use App\Domain\Gateway\Persister\HydrationDayPersisterGateway;

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
