<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister\Training;

use App\Domain\DTO\DataModel\Training\WorkoutSetDataModel;
use App\Domain\Gateway\Persister\Training\WorkoutSetPersisterGateway;
use App\Infrastructure\Persister\AbstractBaseMysqlPersister;

/**
 * @extends AbstractBaseMysqlPersister<WorkoutSetDataModel>
 */
final class WorkoutSetPersister extends AbstractBaseMysqlPersister implements WorkoutSetPersisterGateway
{
    public function create(WorkoutSetDataModel $workoutSet): WorkoutSetDataModel
    {
        return $this->persistAndStampCreate($workoutSet);
    }

    public function update(WorkoutSetDataModel $workoutSet): WorkoutSetDataModel
    {
        return $this->persistAndStampUpdate($workoutSet);
    }

    public function delete(WorkoutSetDataModel $workoutSet): void
    {
        $this->persistDelete($workoutSet);
    }
}
