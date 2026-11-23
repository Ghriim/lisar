<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Provider\Training;

use App\Domain\DTO\DataModel\Training\MovementDataModel;
use App\Domain\DTO\DataModel\Training\RoutineDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;

interface RoutineProviderGateway
{
    /**
     * A routine, whole — its blocks, movements and their families, its sets — by its id alone:
     * whose it is is the validator's to check.
     */
    public function findOneById(int $id): ?RoutineDataModel;

    /**
     * The owner's routines by name, accents and case aside, each with its blocks and movements
     * joined: what a list counts. Their sets are not read.
     *
     * @return list<RoutineDataModel>
     */
    public function findAllForOwner(UserDataModel $owner): array;

    /** How many movements of routines, anyone's, are of that movement. */
    public function countExercisesForMovement(MovementDataModel $movement): int;
}
