<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister\Training;

use App\Domain\DTO\DataModel\Training\WorkoutExerciseDataModel;
use App\Domain\Gateway\Persister\Training\WorkoutExercisePersisterGateway;
use App\Infrastructure\Persister\AbstractBaseMysqlPersister;

/**
 * @extends AbstractBaseMysqlPersister<WorkoutExerciseDataModel>
 */
final class WorkoutExercisePersister extends AbstractBaseMysqlPersister implements WorkoutExercisePersisterGateway
{
    public function create(WorkoutExerciseDataModel $workoutExercise): WorkoutExerciseDataModel
    {
        return $this->persistAndStampCreate($workoutExercise);
    }

    public function update(WorkoutExerciseDataModel $workoutExercise): WorkoutExerciseDataModel
    {
        return $this->persistAndStampUpdate($workoutExercise);
    }

    public function delete(WorkoutExerciseDataModel $workoutExercise): void
    {
        $this->persistDelete($workoutExercise);
    }
}
