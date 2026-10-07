<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister\Tracking\Sleep;

use App\Domain\DTO\DataModel\Tracking\SleepNightDataModel;
use App\Domain\Gateway\Persister\Tracking\Sleep\SleepNightPersisterGateway;
use App\Infrastructure\Persister\AbstractBaseMysqlPersister;

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
