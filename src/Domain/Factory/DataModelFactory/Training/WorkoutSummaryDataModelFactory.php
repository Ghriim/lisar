<?php

declare(strict_types=1);

namespace App\Domain\Factory\DataModelFactory\Training;

use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutSummaryDataModel;

/**
 * A workout's summary, and its totals worked out again from its done sets. The record count is
 * not a total of the sets: the records' own rebuild moves it.
 */
final readonly class WorkoutSummaryDataModelFactory
{
    public function build(WorkoutDataModel $workout): WorkoutSummaryDataModel
    {
        $summary = new WorkoutSummaryDataModel();
        $summary->workout = $workout;
        $this->refresh($summary);

        return $summary;
    }

    public function refresh(WorkoutSummaryDataModel $summary): void
    {
        $workout = $summary->workout;

        $summary->setCount = $workout->countCompleteSets();
        $summary->volumeInKilograms = $workout->volumeInKilograms();
        $summary->durationInSeconds = $workout->durationInSeconds();
        $summary->distanceInMetres = $workout->distanceInMetres();
    }
}
