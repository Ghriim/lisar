<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Provider\Training;

use App\Domain\DTO\DataModel\Training\WorkoutSetDataModel;

interface WorkoutSetProviderGateway
{
    /** A set, by its id alone: which workout it is in is the validator's to check. */
    public function findOneById(int $id): ?WorkoutSetDataModel;
}
