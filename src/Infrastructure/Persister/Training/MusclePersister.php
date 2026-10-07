<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister\Training;

use App\Domain\DTO\DataModel\Training\MuscleDataModel;
use App\Domain\Gateway\Persister\Training\MusclePersisterGateway;
use App\Infrastructure\Persister\AbstractBaseMysqlPersister;

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
