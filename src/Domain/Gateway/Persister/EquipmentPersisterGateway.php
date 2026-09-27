<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Persister;

use App\Domain\DTO\DataModel\EquipmentDataModel;

interface EquipmentPersisterGateway
{
    public function create(EquipmentDataModel $equipment): EquipmentDataModel;

    public function update(EquipmentDataModel $equipment): EquipmentDataModel;

    public function delete(EquipmentDataModel $equipment): void;
}
