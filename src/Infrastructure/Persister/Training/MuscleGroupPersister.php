<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister\Training;

use App\Domain\DTO\DataModel\Training\MuscleGroupDataModel;
use App\Domain\Gateway\Persister\Training\MuscleGroupPersisterGateway;
use App\Infrastructure\Persister\AbstractBaseMysqlPersister;

/**
 * @extends AbstractBaseMysqlPersister<MuscleGroupDataModel>
 */
final class MuscleGroupPersister extends AbstractBaseMysqlPersister implements MuscleGroupPersisterGateway
{
    public function create(MuscleGroupDataModel $muscleGroup): MuscleGroupDataModel
    {
        return $this->persistCreate($muscleGroup);
    }

    public function update(MuscleGroupDataModel $muscleGroup): MuscleGroupDataModel
    {
        return $this->persistUpdate($muscleGroup);
    }

    public function delete(MuscleGroupDataModel $muscleGroup): void
    {
        $this->persistDelete($muscleGroup);
    }
}
