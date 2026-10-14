<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Provider\Training;

use App\Domain\DTO\DataModel\Training\WorkoutBlockDataModel;

interface WorkoutBlockProviderGateway
{
    /** A block, by its id alone: which workout it is in is the validator's to check. */
    public function findOneById(int $id): ?WorkoutBlockDataModel;
}
