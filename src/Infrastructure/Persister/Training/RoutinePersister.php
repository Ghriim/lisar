<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister\Training;

use App\Domain\DTO\DataModel\Training\RoutineDataModel;
use App\Domain\Gateway\Persister\Training\RoutinePersisterGateway;
use App\Infrastructure\Persister\AbstractBaseMysqlPersister;

/**
 * Nothing follows from a routine's writes: no event is raised.
 *
 * @extends AbstractBaseMysqlPersister<RoutineDataModel>
 */
final class RoutinePersister extends AbstractBaseMysqlPersister implements RoutinePersisterGateway
{
    public function create(RoutineDataModel $routine): RoutineDataModel
    {
        return $this->persistCreate($routine);
    }

    public function update(RoutineDataModel $routine): RoutineDataModel
    {
        return $this->persistUpdate($routine);
    }

    public function delete(RoutineDataModel $routine): void
    {
        $this->persistDelete($routine);
    }
}
