<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister;

use App\Domain\DTO\DataModel\MuscleDataModel;
use App\Domain\Gateway\Persister\MusclePersisterGateway;

/**
 * @extends AbstractBaseMysqlPersister<MuscleDataModel>
 */
final class MusclePersister extends AbstractBaseMysqlPersister implements MusclePersisterGateway
{
    public function create(MuscleDataModel $muscle): MuscleDataModel
    {
        return $this->persistAndStampCreate($muscle);
    }

    public function update(MuscleDataModel $muscle): MuscleDataModel
    {
        return $this->persistAndStampUpdate($muscle);
    }

    public function delete(MuscleDataModel $muscle): void
    {
        $this->persistDelete($muscle);
    }
}
