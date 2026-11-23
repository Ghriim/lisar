<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Persister\Training;

use App\Domain\DTO\DataModel\Training\RoutineBlockDataModel;

interface RoutineBlockPersisterGateway
{
    public function create(RoutineBlockDataModel $routineBlock): RoutineBlockDataModel;

    public function update(RoutineBlockDataModel $routineBlock): RoutineBlockDataModel;

    public function delete(RoutineBlockDataModel $routineBlock): void;
}
