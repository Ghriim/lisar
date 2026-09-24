<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Persister;

use App\Domain\DTO\DataModel\HabitEntryDataModel;

interface HabitEntryPersisterGateway
{
    public function create(HabitEntryDataModel $entry): HabitEntryDataModel;

    public function update(HabitEntryDataModel $entry): HabitEntryDataModel;
}
