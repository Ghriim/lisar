<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister\Training;

use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\Gateway\Persister\Training\WorkoutPersisterGateway;
use App\Infrastructure\Persister\AbstractBaseMysqlPersister;

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
