<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Provider;

use App\Domain\DTO\DataModel\MovementFamilyDataModel;

interface MovementFamilyProviderGateway
{
    public function findOneById(int $id): ?MovementFamilyDataModel;

    /** Ignoring case: the column's collation does the comparing. */
    public function findOneByName(string $name): ?MovementFamilyDataModel;

    /**
     * Every family, active or not, by name. Not filtered, for the same reason as the muscle
     * groups: the movement form needs an inactive family to show a movement already in it.
     *
     * @return list<MovementFamilyDataModel>
     */
    public function findAllForAdminList(): array;
}
