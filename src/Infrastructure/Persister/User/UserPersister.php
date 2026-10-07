<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister\User;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Gateway\Persister\User\UserPersisterGateway;
use App\Infrastructure\Persister\AbstractBaseMysqlPersister;

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
