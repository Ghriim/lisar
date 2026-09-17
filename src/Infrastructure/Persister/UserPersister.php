<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister;

use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\Gateway\Persister\UserPersisterGateway;

/**
 * @extends AbstractBaseMysqlPersister<UserDataModel>
 */
final class UserPersister extends AbstractBaseMysqlPersister implements UserPersisterGateway
{
    public function create(UserDataModel $user): UserDataModel
    {
        return $this->persistAndStampCreate($user);
    }

    public function update(UserDataModel $user): UserDataModel
    {
        return $this->persistAndStampUpdate($user);
    }
}
