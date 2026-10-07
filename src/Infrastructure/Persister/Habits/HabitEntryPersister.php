<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister\Habits;

use App\Domain\DTO\DataModel\Habits\HabitEntryDataModel;
use App\Domain\Gateway\Persister\Habits\HabitEntryPersisterGateway;
use App\Infrastructure\Persister\AbstractBaseMysqlPersister;

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
