<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister\Training;

use App\Domain\DTO\DataModel\Training\WorkoutExerciseDataModel;
use App\Domain\DTO\Event\Training\WorkoutExerciseDeletedEvent;
use App\Domain\Event\EventDispatcherInterface;
use App\Domain\Gateway\Persister\Training\WorkoutExercisePersisterGateway;
use App\Infrastructure\Persister\AbstractBaseMysqlPersister;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @extends AbstractBaseMysqlPersister<WorkoutExerciseDataModel>
 */
final class WorkoutExercisePersister extends AbstractBaseMysqlPersister implements WorkoutExercisePersisterGateway
{
    public function __construct(
        EntityManagerInterface $entityManager,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
        parent::__construct($entityManager);
    }

    public function create(WorkoutExerciseDataModel $workoutExercise): WorkoutExerciseDataModel
    {
        $this->persistCreate($workoutExercise);
        if (false === $workoutExercise->block->exercises->contains($workoutExercise)) {
            $workoutExercise->block->exercises->add($workoutExercise);
        }

        return $workoutExercise;
    }

    public function update(WorkoutExerciseDataModel $workoutExercise): WorkoutExerciseDataModel
    {
        return $this->persistUpdate($workoutExercise);
    }

    public function delete(WorkoutExerciseDataModel $workoutExercise): void
    {
        $block = $workoutExercise->block;
        $heldCountingSets = false;
        foreach ($workoutExercise->sets as $set) {
            $heldCountingSets = $heldCountingSets || $set->countsForPersonalBests();
        }
        $event = new WorkoutExerciseDeletedEvent($block->workout, $workoutExercise->movement, $heldCountingSets);

        $this->inTransaction(function () use ($workoutExercise, $block, $event): void {
            $this->persistDelete($workoutExercise);
            $block->exercises->removeElement($workoutExercise);
            $this->eventDispatcher->dispatch($event);
        });
    }
}
