<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister;

use App\Domain\DTO\DataModel\HabitEntryDataModel;
use App\Domain\Gateway\Persister\HabitEntryPersisterGateway;

/**
 * @extends AbstractBaseMysqlPersister<HabitEntryDataModel>
 */
final class HabitEntryPersister extends AbstractBaseMysqlPersister implements HabitEntryPersisterGateway
{
    public function create(HabitEntryDataModel $entry): HabitEntryDataModel
    {
        return $this->persistAndStampCreate($entry);
    }

    public function update(HabitEntryDataModel $entry): HabitEntryDataModel
    {
        return $this->persistAndStampUpdate($entry);
    }
}
