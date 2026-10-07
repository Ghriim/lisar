<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister\Training;

use App\Domain\DTO\DataModel\Training\WorkoutBlockDataModel;
use App\Domain\Gateway\Persister\Training\WorkoutBlockPersisterGateway;
use App\Infrastructure\Persister\AbstractBaseMysqlPersister;

/**
 * @extends AbstractBaseMysqlPersister<WorkoutBlockDataModel>
 */
final class WorkoutBlockPersister extends AbstractBaseMysqlPersister implements WorkoutBlockPersisterGateway
{
    public function create(WorkoutBlockDataModel $workoutBlock): WorkoutBlockDataModel
    {
        return $this->persistAndStampCreate($workoutBlock);
    }

    public function update(WorkoutBlockDataModel $workoutBlock): WorkoutBlockDataModel
    {
        return $this->persistAndStampUpdate($workoutBlock);
    }

    public function delete(WorkoutBlockDataModel $workoutBlock): void
    {
        $this->persistDelete($workoutBlock);
    }
}
