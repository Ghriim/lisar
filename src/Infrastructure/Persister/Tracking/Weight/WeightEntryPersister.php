<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister\Tracking\Weight;

use App\Domain\DTO\DataModel\Tracking\WeightEntryDataModel;
use App\Domain\Gateway\Persister\Tracking\Weight\WeightEntryPersisterGateway;
use App\Infrastructure\Persister\AbstractBaseMysqlPersister;

/**
 * @extends AbstractBaseMysqlPersister<WeightEntryDataModel>
 */
final class WeightEntryPersister extends AbstractBaseMysqlPersister implements WeightEntryPersisterGateway
{
    public function create(WeightEntryDataModel $entry): WeightEntryDataModel
    {
        return $this->persistAndStampCreate($entry);
    }

    public function update(WeightEntryDataModel $entry): WeightEntryDataModel
    {
        return $this->persistAndStampUpdate($entry);
    }
}
