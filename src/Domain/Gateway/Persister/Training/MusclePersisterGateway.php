<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Persister\Training;

use App\Domain\DTO\DataModel\Training\MuscleDataModel;

interface MusclePersisterGateway
{
    public function create(MuscleDataModel $muscle): MuscleDataModel;

    public function update(MuscleDataModel $muscle): MuscleDataModel;

    public function delete(MuscleDataModel $muscle): void;
}
