<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister\Training;

use App\Domain\DTO\DataModel\Training\WorkoutExerciseDataModel;
use App\Domain\DTO\Event\Training\WorkoutExerciseDeletedEvent;
use App\Domain\Event\EventDispatcherInterface;
use App\Domain\Gateway\Persister\Training\WorkoutExercisePersisterGateway;
use App\Infrastructure\Persister\AbstractBaseMysqlPersister;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;

/**
 * @extends AbstractBaseMysqlPersister<WorkoutExerciseDataModel>
 */
final class WorkoutExercisePersister extends AbstractBaseMysqlPersister implements WorkoutExercisePersisterGateway
{
    public function __construct(
        EntityManagerInterface $entityManager,
        ClockInterface $clock,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
        parent::__construct($entityManager, $clock);
    }

    public function create(WorkoutExerciseDataModel $workoutExercise): WorkoutExerciseDataModel
    {
        return $this->persistAndStampCreate($workoutExercise);
    }

    public function update(WorkoutExerciseDataModel $workoutExercise): WorkoutExerciseDataModel
    {
        return $this->persistAndStampUpdate($workoutExercise);
    }

    public function delete(WorkoutExerciseDataModel $workoutExercise): void
    {
        $event = new WorkoutExerciseDeletedEvent($workoutExercise->block->workout->owner, $workoutExercise->movement);

        $this->inTransaction(function () use ($workoutExercise, $event): void {
            $this->persistDelete($workoutExercise);
            $this->eventDispatcher->dispatch($event);
        });
    }
}
