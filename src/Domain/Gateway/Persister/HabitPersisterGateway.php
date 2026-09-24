<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Persister;

use App\Domain\DTO\DataModel\HabitDataModel;

/**
 * No `delete`: a catalogue habit is retired by clearing `isActive`, never dropped, so the days
 * people kept it — and the experience they earned — survive it.
 */
interface HabitPersisterGateway
{
    public function create(HabitDataModel $habit): HabitDataModel;

    public function update(HabitDataModel $habit): HabitDataModel;
}
