<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Provider\Training;

use App\Domain\DTO\DataModel\Training\MovementDataModel;
use App\Domain\DTO\DataModel\Training\PersonalBestDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;

interface PersonalBestProviderGateway
{
    /**
     * The owner's rows for that movement, or for whole workouts when `$movement` is null.
     *
     * @return list<PersonalBestDataModel>
     */
    public function findAllForOwnerAndMovement(UserDataModel $owner, ?MovementDataModel $movement): array;

    /**
     * Every row of the owner, each record's progression oldest first.
     *
     * @return list<PersonalBestDataModel>
     */
    public function findAllForOwner(UserDataModel $owner): array;

    /**
     * The rows that workout beat, by one of its sets or as a whole.
     *
     * @return list<PersonalBestDataModel>
     */
    public function findAllForWorkout(WorkoutDataModel $workout): array;
}
