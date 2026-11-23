<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Persister\Planning;

use App\Domain\DTO\DataModel\Planning\TrainingPlanningEntryDataModel;

interface TrainingPlanningEntryPersisterGateway
{
    public function create(TrainingPlanningEntryDataModel $entry): TrainingPlanningEntryDataModel;

    public function update(TrainingPlanningEntryDataModel $entry): TrainingPlanningEntryDataModel;

    public function delete(TrainingPlanningEntryDataModel $entry): void;
}
