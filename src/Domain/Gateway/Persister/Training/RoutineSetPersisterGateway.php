<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Persister\Training;

use App\Domain\DTO\DataModel\Training\RoutineSetDataModel;

interface RoutineSetPersisterGateway
{
    public function create(RoutineSetDataModel $routineSet): RoutineSetDataModel;

    public function update(RoutineSetDataModel $routineSet): RoutineSetDataModel;

    public function delete(RoutineSetDataModel $routineSet): void;
}
