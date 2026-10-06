<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister;

use App\Domain\DTO\DataModel\SetTypeDataModel;
use App\Domain\Gateway\Persister\SetTypePersisterGateway;

/**
 * @extends AbstractBaseMysqlPersister<SetTypeDataModel>
 */
final class SetTypePersister extends AbstractBaseMysqlPersister implements SetTypePersisterGateway
{
    public function create(SetTypeDataModel $setType): SetTypeDataModel
    {
        return $this->persistAndStampCreate($setType);
    }

    public function update(SetTypeDataModel $setType): SetTypeDataModel
    {
        return $this->persistAndStampUpdate($setType);
    }

    public function delete(SetTypeDataModel $setType): void
    {
        $this->persistDelete($setType);
    }
}
