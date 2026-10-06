<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister;

use App\Domain\DTO\DataModel\WorkoutBlockDataModel;
use App\Domain\Gateway\Persister\WorkoutBlockPersisterGateway;

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
