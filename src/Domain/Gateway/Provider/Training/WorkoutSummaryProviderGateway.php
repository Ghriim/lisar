<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Provider\Training;

use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutSummaryDataModel;

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
}
