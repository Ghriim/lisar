<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Persister;

use App\Domain\DTO\DataModel\UserIdentityDataModel;

interface UserIdentityPersisterGateway
{
    public function create(UserIdentityDataModel $identity): UserIdentityDataModel;

    public function update(UserIdentityDataModel $identity): UserIdentityDataModel;

    public function delete(UserIdentityDataModel $identity): void;
}
