<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister\Training;

use App\Domain\DTO\DataModel\Training\SetTypeDataModel;
use App\Domain\Gateway\Persister\Training\SetTypePersisterGateway;
use App\Infrastructure\Persister\AbstractBaseMysqlPersister;

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

    /** @param SetTypeDataModel[] $setTypes */
    public function updateMany(array $setTypes): void
    {
        foreach ($setTypes as $setType) {
            $this->persistAndStampUpdate($setType, flush: false);
        }

        $this->entityManager->flush();
    }

    public function delete(SetTypeDataModel $setType): void
    {
        $this->persistDelete($setType);
    }
}
