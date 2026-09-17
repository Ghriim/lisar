<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Persister;

use App\Domain\DTO\DataModel\UserDataModel;

interface UserPersisterGateway
{
    public function create(UserDataModel $user): UserDataModel;

    public function update(UserDataModel $user): UserDataModel;
}
