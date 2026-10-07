<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister\Habits;

use App\Domain\DTO\DataModel\Habits\HabitDataModel;
use App\Domain\Gateway\Persister\Habits\HabitPersisterGateway;
use App\Infrastructure\Persister\AbstractBaseMysqlPersister;

/**
 * @extends AbstractBaseMysqlPersister<HabitDataModel>
 */
final class HabitPersister extends AbstractBaseMysqlPersister implements HabitPersisterGateway
{
    public function create(HabitDataModel $habit): HabitDataModel
    {
        return $this->persistAndStampCreate($habit);
    }

    public function update(HabitDataModel $habit): HabitDataModel
    {
        return $this->persistAndStampUpdate($habit);
    }
}
