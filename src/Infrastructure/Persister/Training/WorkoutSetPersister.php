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
use Psr\Clock\ClockInterface;

/**
 * @extends AbstractBaseMysqlPersister<WorkoutSetDataModel>
 */
final class WorkoutSetPersister extends AbstractBaseMysqlPersister implements WorkoutSetPersisterGateway
{
    public function __construct(
        EntityManagerInterface $entityManager,
        ClockInterface $clock,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
        parent::__construct($entityManager, $clock);
    }

    public function create(WorkoutSetDataModel $workoutSet): WorkoutSetDataModel
    {
        $this->inTransaction(function () use ($workoutSet): void {
            $this->persistAndStampCreate($workoutSet);
            $this->eventDispatcher->dispatch(new WorkoutSetCreatedEvent($workoutSet));
        });

        return $workoutSet;
    }

    public function update(WorkoutSetDataModel $workoutSet): WorkoutSetDataModel
    {
        $this->inTransaction(function () use ($workoutSet): void {
            $this->persistAndStampUpdate($workoutSet);
            $this->eventDispatcher->dispatch(new WorkoutSetUpdatedEvent($workoutSet));
        });

        return $workoutSet;
    }

    public function delete(WorkoutSetDataModel $workoutSet): void
    {
        $event = new WorkoutSetDeletedEvent($workoutSet->exercise->block->workout->owner, $workoutSet->exercise->movement);

        $this->inTransaction(function () use ($workoutSet, $event): void {
            $this->persistDelete($workoutSet);
            $this->eventDispatcher->dispatch($event);
        });
    }
}
