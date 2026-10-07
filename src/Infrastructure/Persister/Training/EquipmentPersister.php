<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister\Training;

use App\Domain\DTO\DataModel\Training\EquipmentDataModel;
use App\Domain\Gateway\Persister\Training\EquipmentPersisterGateway;
use App\Infrastructure\Persister\AbstractBaseMysqlPersister;

/**
 * @extends AbstractBaseMysqlPersister<EquipmentDataModel>
 */
final class EquipmentPersister extends AbstractBaseMysqlPersister implements EquipmentPersisterGateway
{
    public function create(EquipmentDataModel $equipment): EquipmentDataModel
    {
        return $this->persistAndStampCreate($equipment);
    }

    public function update(EquipmentDataModel $equipment): EquipmentDataModel
    {
        return $this->persistAndStampUpdate($equipment);
    }

    public function delete(EquipmentDataModel $equipment): void
    {
        $this->persistDelete($equipment);
    }
}
