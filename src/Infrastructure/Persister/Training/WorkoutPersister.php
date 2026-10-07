<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister\Training;

use App\Domain\DTO\DataModel\Training\WorkoutBlockDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutExerciseDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutSetDataModel;
use App\Domain\Gateway\Persister\Training\WorkoutPersisterGateway;
use App\Infrastructure\Persister\AbstractBaseMysqlPersister;
use DateTimeImmutable;

/**
 * @extends AbstractBaseMysqlPersister<WorkoutDataModel>
 */
final class WorkoutPersister extends AbstractBaseMysqlPersister implements WorkoutPersisterGateway
{
    public function create(WorkoutDataModel $workout): WorkoutDataModel
    {
        return $this->persistAndStampCreate($workout);
    }

    public function createWhole(WorkoutDataModel $workout): WorkoutDataModel
    {
        // One flush for the whole tree. It has to be: the blocks are not cascaded, so the workout
        // cannot be flushed while they are not persisted — and a copy half written would be a
        // workout nobody laid out.
        $this->persistAndStampCreate($workout, flush: false);
        foreach ($workout->blocks as $block) {
            $this->persistChild($block);
            foreach ($block->exercises as $exercise) {
                $this->persistChild($exercise);
                foreach ($exercise->sets as $set) {
                    $this->persistChild($set);
                }
            }
        }
        $this->entityManager->flush();

        return $workout;
    }

    public function update(WorkoutDataModel $workout): WorkoutDataModel
    {
        return $this->persistAndStampUpdate($workout);
    }

    public function delete(WorkoutDataModel $workout): void
    {
        $this->persistDelete($workout);
    }

    /** Stamped like the workout itself; the base class only takes the workout. */
    private function persistChild(WorkoutBlockDataModel|WorkoutExerciseDataModel|WorkoutSetDataModel $child): void
    {
        $now = DateTimeImmutable::createFromInterface($this->clock->now());
        $child->createdAt = $now;
        $child->updatedAt = $now;

        $this->entityManager->persist($child);
    }
}
