<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister\Tracking\Step;

use App\Domain\DTO\DataModel\Tracking\StepDayDataModel;
use App\Domain\Gateway\Persister\Tracking\Step\StepDayPersisterGateway;
use App\Infrastructure\Persister\AbstractBaseMysqlPersister;

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
