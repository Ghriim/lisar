<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister;

use App\Domain\DTO\DataModel\MuscleGroupDataModel;
use App\Domain\Gateway\Persister\MuscleGroupPersisterGateway;

/**
 * @extends AbstractBaseMysqlPersister<MuscleGroupDataModel>
 */
final class MuscleGroupPersister extends AbstractBaseMysqlPersister implements MuscleGroupPersisterGateway
{
    public function create(MuscleGroupDataModel $muscleGroup): MuscleGroupDataModel
    {
        return $this->persistAndStampCreate($muscleGroup);
    }

    public function update(MuscleGroupDataModel $muscleGroup): MuscleGroupDataModel
    {
        return $this->persistAndStampUpdate($muscleGroup);
    }

    public function delete(MuscleGroupDataModel $muscleGroup): void
    {
        $this->persistDelete($muscleGroup);
    }
}
