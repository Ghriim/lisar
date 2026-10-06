<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister;

use App\Domain\DTO\DataModel\WorkoutDataModel;
use App\Domain\Gateway\Persister\WorkoutPersisterGateway;

/**
 * @extends AbstractBaseMysqlPersister<WorkoutDataModel>
 */
final class WorkoutPersister extends AbstractBaseMysqlPersister implements WorkoutPersisterGateway
{
    public function create(WorkoutDataModel $workout): WorkoutDataModel
    {
        return $this->persistAndStampCreate($workout);
    }

    public function update(WorkoutDataModel $workout): WorkoutDataModel
    {
        return $this->persistAndStampUpdate($workout);
    }

    public function delete(WorkoutDataModel $workout): void
    {
        $this->persistDelete($workout);
    }
}
