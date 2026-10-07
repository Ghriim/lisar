<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister\User;

use App\Domain\DTO\DataModel\User\UserIdentityDataModel;
use App\Domain\Gateway\Persister\User\UserIdentityPersisterGateway;
use App\Infrastructure\Persister\AbstractBaseMysqlPersister;

/**
 * @extends AbstractBaseMysqlPersister<UserIdentityDataModel>
 */
final class UserIdentityPersister extends AbstractBaseMysqlPersister implements UserIdentityPersisterGateway
{
    public function create(UserIdentityDataModel $identity): UserIdentityDataModel
    {
        return $this->persistAndStampCreate($identity);
    }

    public function update(UserIdentityDataModel $identity): UserIdentityDataModel
    {
        return $this->persistAndStampUpdate($identity);
    }

    public function delete(UserIdentityDataModel $identity): void
    {
        $this->persistDelete($identity);
    }
}
