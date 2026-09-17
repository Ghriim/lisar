<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister;

use App\Domain\DTO\DataModel\PriorityDataModel;
use App\Domain\Gateway\Persister\PriorityPersisterGateway;

/**
 * @extends AbstractBaseMysqlPersister<PriorityDataModel>
 */
final class PriorityPersister extends AbstractBaseMysqlPersister implements PriorityPersisterGateway
{
    public function create(PriorityDataModel $priority): PriorityDataModel
    {
        return $this->persistAndStampCreate($priority);
    }

    public function update(PriorityDataModel $priority): PriorityDataModel
    {
        return $this->persistAndStampUpdate($priority);
    }

    /** @param PriorityDataModel[] $priorities */
    public function updateMany(array $priorities): void
    {
        foreach ($priorities as $priority) {
            $this->persistAndStampUpdate($priority, flush: false);
        }

        $this->entityManager->flush();
    }

    public function delete(PriorityDataModel $priority): void
    {
        $this->persistDelete($priority);
    }
}
