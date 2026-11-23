<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister\Training;

use App\Domain\DTO\DataModel\Training\RoutineExerciseDataModel;
use App\Domain\Gateway\Persister\Training\RoutineExercisePersisterGateway;
use App\Infrastructure\Persister\AbstractBaseMysqlPersister;

/**
 * Keeps its parent's `exercises` in step in memory: the routine answered after a write is read off
 * what the request holds.
 *
 * @extends AbstractBaseMysqlPersister<RoutineExerciseDataModel>
 */
final class RoutineExercisePersister extends AbstractBaseMysqlPersister implements RoutineExercisePersisterGateway
{
    public function create(RoutineExerciseDataModel $routineExercise): RoutineExerciseDataModel
    {
        $this->persistCreate($routineExercise);
        if (false === $routineExercise->block->exercises->contains($routineExercise)) {
            $routineExercise->block->exercises->add($routineExercise);
        }

        return $routineExercise;
    }

    public function update(RoutineExerciseDataModel $routineExercise): RoutineExerciseDataModel
    {
        return $this->persistUpdate($routineExercise);
    }

    public function delete(RoutineExerciseDataModel $routineExercise): void
    {
        $this->persistDelete($routineExercise);
        $routineExercise->block->exercises->removeElement($routineExercise);
    }
}
