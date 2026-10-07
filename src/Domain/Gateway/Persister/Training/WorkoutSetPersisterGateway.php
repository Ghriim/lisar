<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Persister\Training;

use App\Domain\DTO\DataModel\Training\WorkoutSetDataModel;

interface WorkoutSetPersisterGateway
{
    public function create(WorkoutSetDataModel $workoutSet): WorkoutSetDataModel;

    public function update(WorkoutSetDataModel $workoutSet): WorkoutSetDataModel;

    public function delete(WorkoutSetDataModel $workoutSet): void;
}
