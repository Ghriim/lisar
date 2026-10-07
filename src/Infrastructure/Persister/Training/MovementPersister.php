<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister\Training;

use App\Domain\DTO\DataModel\Training\MovementDataModel;
use App\Domain\Gateway\Persister\Training\MovementPersisterGateway;
use App\Infrastructure\Persister\AbstractBaseMysqlPersister;

/**
 * @extends AbstractBaseMysqlPersister<MovementDataModel>
 */
final class MovementPersister extends AbstractBaseMysqlPersister implements MovementPersisterGateway
{
    public function create(MovementDataModel $movement): MovementDataModel
    {
        return $this->persistAndStampCreate($movement);
    }

    public function update(MovementDataModel $movement): MovementDataModel
    {
        return $this->persistAndStampUpdate($movement);
    }

    public function delete(MovementDataModel $movement): void
    {
        $this->persistDelete($movement);
    }
}
