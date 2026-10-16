<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister\Training;

use App\Domain\DTO\DataModel\Training\WorkoutSummaryDataModel;
use App\Domain\Gateway\Persister\Training\WorkoutSummaryPersisterGateway;
use App\Infrastructure\Persister\AbstractBaseMysqlPersister;

/**
 * Written by event handlers only, so it raises nothing.
 *
 * @extends AbstractBaseMysqlPersister<WorkoutSummaryDataModel>
 */
final class WorkoutSummaryPersister extends AbstractBaseMysqlPersister implements WorkoutSummaryPersisterGateway
{
    public function create(WorkoutSummaryDataModel $summary): WorkoutSummaryDataModel
    {
        return $this->persistCreate($summary);
    }

    /** @param list<WorkoutSummaryDataModel> $summaries */
    public function updateMany(array $summaries): void
    {
        foreach ($summaries as $summary) {
            $this->persistUpdate($summary, flush: false);
        }
        $this->entityManager->flush();
    }
}
