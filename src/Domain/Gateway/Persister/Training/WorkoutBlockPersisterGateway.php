<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Persister\Training;

use App\Domain\DTO\DataModel\Training\WorkoutBlockDataModel;

interface WorkoutBlockPersisterGateway
{
    public function create(WorkoutBlockDataModel $workoutBlock): WorkoutBlockDataModel;

    public function update(WorkoutBlockDataModel $workoutBlock): WorkoutBlockDataModel;

    public function delete(WorkoutBlockDataModel $workoutBlock): void;
}
