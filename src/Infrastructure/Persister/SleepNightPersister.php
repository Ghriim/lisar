<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister;

use App\Domain\DTO\DataModel\SleepNightDataModel;
use App\Domain\Gateway\Persister\SleepNightPersisterGateway;

/**
 * @extends AbstractBaseMysqlPersister<SleepNightDataModel>
 */
final class SleepNightPersister extends AbstractBaseMysqlPersister implements SleepNightPersisterGateway
{
    public function create(SleepNightDataModel $night): SleepNightDataModel
    {
        return $this->persistAndStampCreate($night);
    }

    public function update(SleepNightDataModel $night): SleepNightDataModel
    {
        return $this->persistAndStampUpdate($night);
    }
}
