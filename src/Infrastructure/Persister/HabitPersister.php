<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister;

use App\Domain\DTO\DataModel\HabitDataModel;
use App\Domain\Gateway\Persister\HabitPersisterGateway;

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
