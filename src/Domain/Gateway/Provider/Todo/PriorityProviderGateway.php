<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Provider\Todo;

use App\Domain\DTO\DataModel\Todo\PriorityDataModel;

interface PriorityProviderGateway
{
    public function findOneById(int $id): ?PriorityDataModel;

    /** The one that applies to a task created without a priority. */
    public function findOneDefault(): ?PriorityDataModel;

    /** @return list<PriorityDataModel> */
    public function findAllOrderedByWeight(): array;
}
