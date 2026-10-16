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
     * What a rebuild of the records replaces: the owner's rows for whole workouts, of no movement,
     * and their rows for those movements, in one read.
     *
     * @param list<MovementDataModel> $movements none for a write that moves only the workouts' records
     *
     * @return list<PersonalBestDataModel>
     */
    public function findPersonalBestsForRefreshEvent(UserDataModel $owner, array $movements): array;

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
