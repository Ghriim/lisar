<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister\Training;

use App\Domain\DTO\DataModel\Training\WorkoutSetDataModel;
use App\Domain\DTO\Event\Training\WorkoutSetCreatedEvent;
use App\Domain\DTO\Event\Training\WorkoutSetDeletedEvent;
use App\Domain\DTO\Event\Training\WorkoutSetUpdatedEvent;
use App\Domain\Event\EventDispatcherInterface;
use App\Domain\Gateway\Persister\Training\WorkoutSetPersisterGateway;
use App\Infrastructure\Persister\AbstractBaseMysqlPersister;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @extends AbstractBaseMysqlPersister<WorkoutSetDataModel>
 */
final class WorkoutSetPersister extends AbstractBaseMysqlPersister implements WorkoutSetPersisterGateway
{
    public function __construct(
        EntityManagerInterface $entityManager,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
        parent::__construct($entityManager);
    }

    public function create(WorkoutSetDataModel $workoutSet): WorkoutSetDataModel
    {
        $this->inTransaction(function () use ($workoutSet): void {
            $this->persistCreate($workoutSet);
            // Both sides kept in step before anyone adds the workout up off its sets.
            if (false === $workoutSet->exercise->sets->contains($workoutSet)) {
                $workoutSet->exercise->sets->add($workoutSet);
            }
            $this->eventDispatcher->dispatch(new WorkoutSetCreatedEvent($workoutSet));
        });

        return $workoutSet;
    }

    public function update(WorkoutSetDataModel $workoutSet): WorkoutSetDataModel
    {
        // Read before the flush forgets it: a set done neither before nor after moves no record.
        $wasCompleteBefore = true === $this->originalValueOf($workoutSet, 'isComplete');

        $this->inTransaction(function () use ($workoutSet, $wasCompleteBefore): void {
            $this->persistUpdate($workoutSet);
            $this->eventDispatcher->dispatch(new WorkoutSetUpdatedEvent($workoutSet, $wasCompleteBefore));
        });

        return $workoutSet;
    }

    public function delete(WorkoutSetDataModel $workoutSet): void
    {
        $exercise = $workoutSet->exercise;
        $event = new WorkoutSetDeletedEvent($exercise->block->workout, $exercise->movement, $workoutSet->isComplete);

        $this->inTransaction(function () use ($workoutSet, $exercise, $event): void {
            $this->persistDelete($workoutSet);
            $exercise->sets->removeElement($workoutSet);
            $this->eventDispatcher->dispatch($event);
        });
    }
}
