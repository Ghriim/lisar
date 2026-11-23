<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister\Planning;

use App\Domain\DTO\DataModel\Planning\TrainingPlanningEntryDataModel;
use App\Domain\Gateway\Persister\Planning\TrainingPlanningEntryPersisterGateway;
use App\Infrastructure\Persister\AbstractBaseMysqlPersister;

/**
 * Nothing follows from a training entry's writes: no event is raised. Whether a slot is kept is
 * read from the workouts, never written.
 *
 * @extends AbstractBaseMysqlPersister<TrainingPlanningEntryDataModel>
 */
final class TrainingPlanningEntryPersister extends AbstractBaseMysqlPersister implements TrainingPlanningEntryPersisterGateway
{
    public function create(TrainingPlanningEntryDataModel $entry): TrainingPlanningEntryDataModel
    {
        return $this->persistCreate($entry);
    }

    public function update(TrainingPlanningEntryDataModel $entry): TrainingPlanningEntryDataModel
    {
        return $this->persistUpdate($entry);
    }

    public function delete(TrainingPlanningEntryDataModel $entry): void
    {
        $this->persistDelete($entry);
    }
}
