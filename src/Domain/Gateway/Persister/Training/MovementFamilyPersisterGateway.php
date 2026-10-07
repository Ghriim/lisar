<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Persister\Training;

use App\Domain\DTO\DataModel\Training\MovementFamilyDataModel;

interface MovementFamilyPersisterGateway
{
    public function create(MovementFamilyDataModel $movementFamily): MovementFamilyDataModel;

    public function update(MovementFamilyDataModel $movementFamily): MovementFamilyDataModel;

    public function delete(MovementFamilyDataModel $movementFamily): void;
}
