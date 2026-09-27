<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Provider;

use App\Domain\DTO\DataModel\MuscleGroupDataModel;

interface MuscleGroupProviderGateway
{
    public function findOneById(int $id): ?MuscleGroupDataModel;

    /** Ignoring case: the column's collation does the comparing. */
    public function findOneByName(string $name): ?MuscleGroupDataModel;

    /**
     * Every group, active or not, by name. Not filtered: there are a handful of them, and the
     * muscle form needs the inactive ones to show a muscle that already sits in one.
     *
     * @return list<MuscleGroupDataModel>
     */
    public function findAllForAdminList(): array;
}
