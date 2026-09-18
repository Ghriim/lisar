<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister;

use App\Domain\DTO\DataModel\WeightEntryDataModel;
use App\Domain\Gateway\Persister\WeightEntryPersisterGateway;

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
