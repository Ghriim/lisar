<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister;

use App\Domain\DTO\DataModel\MovementFamilyDataModel;
use App\Domain\Gateway\Persister\MovementFamilyPersisterGateway;

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
