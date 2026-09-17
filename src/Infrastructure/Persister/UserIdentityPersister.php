<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister;

use App\Domain\DTO\DataModel\UserIdentityDataModel;
use App\Domain\Gateway\Persister\UserIdentityPersisterGateway;

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
