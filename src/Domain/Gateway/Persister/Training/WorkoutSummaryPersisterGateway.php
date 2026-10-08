<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Persister\Training;

use App\Domain\DTO\DataModel\Training\WorkoutSummaryDataModel;

interface WorkoutSummaryPersisterGateway
{
    public function create(WorkoutSummaryDataModel $summary): WorkoutSummaryDataModel;

    /** @param list<WorkoutSummaryDataModel> $summaries */
    public function updateMany(array $summaries): void;
}
