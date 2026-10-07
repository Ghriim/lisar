<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Persister\Training;

use App\Domain\DTO\DataModel\Training\WorkoutExerciseDataModel;

interface WorkoutExercisePersisterGateway
{
    public function create(WorkoutExerciseDataModel $workoutExercise): WorkoutExerciseDataModel;

    public function update(WorkoutExerciseDataModel $workoutExercise): WorkoutExerciseDataModel;

    public function delete(WorkoutExerciseDataModel $workoutExercise): void;
}
