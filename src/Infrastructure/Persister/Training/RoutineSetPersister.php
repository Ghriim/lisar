<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister\Training;

use App\Domain\DTO\DataModel\Training\RoutineSetDataModel;
use App\Domain\Gateway\Persister\Training\RoutineSetPersisterGateway;
use App\Infrastructure\Persister\AbstractBaseMysqlPersister;

/**
 * Keeps its parent's `sets` in step in memory: the routine answered after a write is read off
 * what the request holds.
 *
 * @extends AbstractBaseMysqlPersister<RoutineSetDataModel>
 */
final class RoutineSetPersister extends AbstractBaseMysqlPersister implements RoutineSetPersisterGateway
{
    public function create(RoutineSetDataModel $routineSet): RoutineSetDataModel
    {
        $this->persistCreate($routineSet);
        if (false === $routineSet->exercise->sets->contains($routineSet)) {
            $routineSet->exercise->sets->add($routineSet);
        }

        return $routineSet;
    }

    public function update(RoutineSetDataModel $routineSet): RoutineSetDataModel
    {
        return $this->persistUpdate($routineSet);
    }

    public function delete(RoutineSetDataModel $routineSet): void
    {
        $this->persistDelete($routineSet);
        $routineSet->exercise->sets->removeElement($routineSet);
    }
}
