<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister\Training;

use App\Domain\DTO\DataModel\Training\MovementFamilyDataModel;
use App\Domain\Gateway\Persister\Training\MovementFamilyPersisterGateway;
use App\Infrastructure\Persister\AbstractBaseMysqlPersister;

/**
 * @extends AbstractBaseMysqlPersister<MovementFamilyDataModel>
 */
final class MovementFamilyPersister extends AbstractBaseMysqlPersister implements MovementFamilyPersisterGateway
{
    public function create(MovementFamilyDataModel $movementFamily): MovementFamilyDataModel
    {
        return $this->persistAndStampCreate($movementFamily);
    }

    public function update(MovementFamilyDataModel $movementFamily): MovementFamilyDataModel
    {
        return $this->persistAndStampUpdate($movementFamily);
    }

    public function delete(MovementFamilyDataModel $movementFamily): void
    {
        $this->persistDelete($movementFamily);
    }
}
