<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Provider\Training;

use App\Domain\DTO\DataModel\Training\SetTypeDataModel;

interface SetTypeProviderGateway
{
    public function findOneById(int $id): ?SetTypeDataModel;

    /** Ignoring case: the column's collation does the comparing. */
    public function findOneByName(string $name): ?SetTypeDataModel;

    /**
     * Every set type for the back-office, by name. `$isActive` narrows it when it is not null —
     * true for the offered ones, false for the retired ones.
     *
     * @return list<SetTypeDataModel>
     */
    public function findAllForAdminList(?bool $isActive): array;

    /**
     * The set types a set may take on now, by name.
     *
     * @return list<SetTypeDataModel>
     */
    public function findAllActive(): array;
}
