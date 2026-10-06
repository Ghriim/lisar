<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Persister;

use App\Domain\DTO\DataModel\WorkoutDataModel;

interface WorkoutPersisterGateway
{
    public function create(WorkoutDataModel $workout): WorkoutDataModel;

    public function update(WorkoutDataModel $workout): WorkoutDataModel;

    public function delete(WorkoutDataModel $workout): void;
}
