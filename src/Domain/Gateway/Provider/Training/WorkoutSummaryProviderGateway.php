<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Provider\Training;

use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutSummaryDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;

interface WorkoutSummaryProviderGateway
{
    public function findOneForWorkout(WorkoutDataModel $workout): ?WorkoutSummaryDataModel;

    /**
     * The summaries of those workouts, by workout id.
     *
     * @param list<WorkoutDataModel> $workouts
     *
     * @return array<int, WorkoutSummaryDataModel>
     */
    public function findAllForWorkouts(array $workouts): array;

    /**
     * Every summary of the owner's workouts, oldest workout first. The workout is joined, ready
     * to read.
     *
     * @return list<WorkoutSummaryDataModel>
     */
    public function findAllForOwner(UserDataModel $owner): array;
}
