<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Provider;

use App\Domain\DTO\DataModel\EquipmentDataModel;

interface EquipmentProviderGateway
{
    public function findOneById(int $id): ?EquipmentDataModel;

    /** Ignoring case: the column's collation does the comparing. */
    public function findOneByName(string $name): ?EquipmentDataModel;

    /**
     * Every equipment for the back-office, by name. `$isActive` narrows it: true for the offered
     * ones, false for the retired ones, null for both.
     *
     * @return list<EquipmentDataModel>
     */
    public function findAllForAdminList(?bool $isActive): array;
}
