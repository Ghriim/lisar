<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Persister;

use App\Domain\DTO\DataModel\PriorityDataModel;

interface PriorityPersisterGateway
{
    public function create(PriorityDataModel $priority): PriorityDataModel;

    public function update(PriorityDataModel $priority): PriorityDataModel;

    /** @param PriorityDataModel[] $priorities */
    public function updateMany(array $priorities): void;

    public function delete(PriorityDataModel $priority): void;
}
