<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Persister;

use App\Domain\DTO\DataModel\MovementDataModel;

interface MovementPersisterGateway
{
    public function create(MovementDataModel $movement): MovementDataModel;

    public function update(MovementDataModel $movement): MovementDataModel;

    public function delete(MovementDataModel $movement): void;
}
