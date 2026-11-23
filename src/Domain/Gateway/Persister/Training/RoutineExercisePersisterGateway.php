<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Persister\Training;

use App\Domain\DTO\DataModel\Training\RoutineExerciseDataModel;

interface RoutineExercisePersisterGateway
{
    public function create(RoutineExerciseDataModel $routineExercise): RoutineExerciseDataModel;

    public function update(RoutineExerciseDataModel $routineExercise): RoutineExerciseDataModel;

    public function delete(RoutineExerciseDataModel $routineExercise): void;
}
