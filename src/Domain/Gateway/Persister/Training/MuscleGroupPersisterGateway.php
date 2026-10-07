<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Persister\Training;

use App\Domain\DTO\DataModel\Training\MuscleGroupDataModel;

interface MuscleGroupPersisterGateway
{
    public function create(MuscleGroupDataModel $muscleGroup): MuscleGroupDataModel;

    public function update(MuscleGroupDataModel $muscleGroup): MuscleGroupDataModel;

    public function delete(MuscleGroupDataModel $muscleGroup): void;
}
