<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister;

use App\Domain\DTO\DataModel\WorkoutSetDataModel;
use App\Domain\Gateway\Persister\WorkoutSetPersisterGateway;

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
