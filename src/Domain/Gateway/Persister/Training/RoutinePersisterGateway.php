<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Persister\Training;

use App\Domain\DTO\DataModel\Training\RoutineDataModel;

interface RoutinePersisterGateway
{
    /** Persists the routine and, through the cascade, every block, movement and set it holds. */
    public function create(RoutineDataModel $routine): RoutineDataModel;

    public function update(RoutineDataModel $routine): RoutineDataModel;

    public function delete(RoutineDataModel $routine): void;
}
