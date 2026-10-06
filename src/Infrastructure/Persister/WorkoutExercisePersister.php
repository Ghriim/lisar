<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister;

use App\Domain\DTO\DataModel\WorkoutExerciseDataModel;
use App\Domain\Gateway\Persister\WorkoutExercisePersisterGateway;

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
