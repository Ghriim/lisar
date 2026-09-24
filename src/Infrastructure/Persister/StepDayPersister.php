<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister;

use App\Domain\DTO\DataModel\StepDayDataModel;
use App\Domain\Gateway\Persister\StepDayPersisterGateway;

/**
 * @extends AbstractBaseMysqlPersister<StepDayDataModel>
 */
final class StepDayPersister extends AbstractBaseMysqlPersister implements StepDayPersisterGateway
{
    public function create(StepDayDataModel $stepDay): StepDayDataModel
    {
        return $this->persistAndStampCreate($stepDay);
    }

    public function update(StepDayDataModel $stepDay): StepDayDataModel
    {
        return $this->persistAndStampUpdate($stepDay);
    }
}
