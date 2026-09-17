<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Provider;

use App\Domain\DTO\DataModel\CategoryDataModel;
use App\Domain\DTO\DataModel\UserDataModel;

interface CategoryProviderGateway
{
    public function findOneById(int $id): ?CategoryDataModel;

    /**
     * The reference categories plus that account's own, which is exactly what it may use.
     *
     * @return list<CategoryDataModel>
     */
    public function findAllUsableBy(UserDataModel $user): array;

    /** @return list<CategoryDataModel> */
    public function findAllReference(): array;

    /** Used to keep labels unique: pass null as the owner for the reference set. */
    public function findOneByLabelForOwner(string $label, ?UserDataModel $owner): ?CategoryDataModel;
}
