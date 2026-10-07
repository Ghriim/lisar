<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister\Tracking\Hydration;

use App\Domain\DTO\DataModel\Tracking\HydrationEntryDataModel;
use App\Domain\Gateway\Persister\Tracking\Hydration\HydrationEntryPersisterGateway;
use App\Infrastructure\Persister\AbstractBaseMysqlPersister;

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
