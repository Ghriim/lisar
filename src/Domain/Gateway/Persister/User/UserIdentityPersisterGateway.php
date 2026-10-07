<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Persister\User;

use App\Domain\DTO\DataModel\User\UserIdentityDataModel;

interface UserIdentityPersisterGateway
{
    public function create(UserIdentityDataModel $identity): UserIdentityDataModel;

    public function update(UserIdentityDataModel $identity): UserIdentityDataModel;

    public function delete(UserIdentityDataModel $identity): void;
}
