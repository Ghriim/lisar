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
        return $this->persistCreate($habit);
    }

    public function update(HabitDataModel $habit): HabitDataModel
    {
        return $this->persistUpdate($habit);
    }
}
