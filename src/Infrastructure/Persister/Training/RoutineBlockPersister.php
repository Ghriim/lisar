<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister\Training;

use App\Domain\DTO\DataModel\Training\RoutineBlockDataModel;
use App\Domain\Gateway\Persister\Training\RoutineBlockPersisterGateway;
use App\Infrastructure\Persister\AbstractBaseMysqlPersister;

/**
 * Keeps its parent's `blocks` in step in memory: the routine answered after a write is read off
 * what the request holds.
 *
 * @extends AbstractBaseMysqlPersister<RoutineBlockDataModel>
 */
final class RoutineBlockPersister extends AbstractBaseMysqlPersister implements RoutineBlockPersisterGateway
{
    public function create(RoutineBlockDataModel $routineBlock): RoutineBlockDataModel
    {
        $this->persistCreate($routineBlock);
        if (false === $routineBlock->routine->blocks->contains($routineBlock)) {
            $routineBlock->routine->blocks->add($routineBlock);
        }

        return $routineBlock;
    }

    public function update(RoutineBlockDataModel $routineBlock): RoutineBlockDataModel
    {
        return $this->persistUpdate($routineBlock);
    }

    public function delete(RoutineBlockDataModel $routineBlock): void
    {
        $this->persistDelete($routineBlock);
        $routineBlock->routine->blocks->removeElement($routineBlock);
    }
}
