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
     * The owner's rows for those movements.
     *
     * @param list<MovementDataModel> $movements
     *
     * @return list<PersonalBestDataModel>
     */
    public function findAllForOwnerAndMovements(UserDataModel $owner, array $movements): array;

    /**
     * The owner's rows for whole workouts, of no movement.
     *
     * @return list<PersonalBestDataModel>
     */
    public function findAllSessionsForOwner(UserDataModel $owner): array;

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

    /**
     * How many rows each of those workouts beat, by workout id. A workout that beat none is
     * absent.
     *
     * @param list<WorkoutDataModel> $workouts
     *
     * @return array<int, int>
     */
    public function countByWorkout(array $workouts): array;
}
