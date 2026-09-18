<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister;

use App\Domain\DTO\DataModel\HydrationEntryDataModel;
use App\Domain\Gateway\Persister\HydrationEntryPersisterGateway;

/**
 * @extends AbstractBaseMysqlPersister<HydrationEntryDataModel>
 */
final class HydrationEntryPersister extends AbstractBaseMysqlPersister implements HydrationEntryPersisterGateway
{
    public function create(HydrationEntryDataModel $entry): HydrationEntryDataModel
    {
        return $this->persistAndStampCreate($entry);
    }

    public function update(HydrationEntryDataModel $entry): HydrationEntryDataModel
    {
        return $this->persistAndStampUpdate($entry);
    }

    public function delete(HydrationEntryDataModel $entry): void
    {
        $this->persistDelete($entry);
    }
}
