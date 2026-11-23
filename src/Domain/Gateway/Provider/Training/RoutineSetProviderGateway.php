<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Provider\Training;

use App\Domain\DTO\DataModel\Training\RoutineSetDataModel;

interface RoutineSetProviderGateway
{
    /** A prescribed set, by its id alone: which routine it is in is the validator's to check. */
    public function findOneById(int $id): ?RoutineSetDataModel;
}
