<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Persister;

use App\Domain\DTO\DataModel\WorkoutExerciseDataModel;

interface WorkoutExercisePersisterGateway
{
    public function create(WorkoutExerciseDataModel $workoutExercise): WorkoutExerciseDataModel;

    public function update(WorkoutExerciseDataModel $workoutExercise): WorkoutExerciseDataModel;

    public function delete(WorkoutExerciseDataModel $workoutExercise): void;
}
