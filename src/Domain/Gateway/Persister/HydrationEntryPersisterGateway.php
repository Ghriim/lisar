<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Persister;

use App\Domain\DTO\DataModel\HydrationEntryDataModel;

interface HydrationEntryPersisterGateway
{
    public function create(HydrationEntryDataModel $entry): HydrationEntryDataModel;

    public function update(HydrationEntryDataModel $entry): HydrationEntryDataModel;

    public function delete(HydrationEntryDataModel $entry): void;
}
