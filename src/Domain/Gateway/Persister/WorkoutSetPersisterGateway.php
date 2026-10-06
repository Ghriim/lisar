<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Persister;

use App\Domain\DTO\DataModel\WorkoutSetDataModel;

interface WorkoutSetPersisterGateway
{
    public function create(WorkoutSetDataModel $workoutSet): WorkoutSetDataModel;

    public function update(WorkoutSetDataModel $workoutSet): WorkoutSetDataModel;

    public function delete(WorkoutSetDataModel $workoutSet): void;
}
