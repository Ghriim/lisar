<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Provider\Training;

use App\Domain\DTO\DataModel\Training\RoutineExerciseDataModel;

interface RoutineExerciseProviderGateway
{
    /** A movement of a routine, by its id alone: which routine it is in is the validator's to check. */
    public function findOneById(int $id): ?RoutineExerciseDataModel;
}
