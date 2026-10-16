<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister\Training;

use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\Event\Training\WorkoutCreatedEvent;
use App\Domain\DTO\Event\Training\WorkoutDeletedEvent;
use App\Domain\DTO\Event\Training\WorkoutUpdatedEvent;
use App\Domain\Event\EventDispatcherInterface;
use App\Domain\Gateway\Persister\Training\WorkoutPersisterGateway;
use App\Infrastructure\Persister\AbstractBaseMysqlPersister;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @extends AbstractBaseMysqlPersister<WorkoutDataModel>
 */
final class WorkoutPersister extends AbstractBaseMysqlPersister implements WorkoutPersisterGateway
{
    public function __construct(
        EntityManagerInterface $entityManager,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
        parent::__construct($entityManager);
    }

    public function create(WorkoutDataModel $workout): WorkoutDataModel
    {
        // Its blocks, their movements and their sets go with it — persisted by cascade, in one
        // flush: a copy half written would be a workout nobody laid out.
        $this->inTransaction(function () use ($workout): void {
            $this->persistCreate($workout);
            $this->eventDispatcher->dispatch(new WorkoutCreatedEvent($workout));
        });

        return $workout;
    }

    public function update(WorkoutDataModel $workout): WorkoutDataModel
    {
        $this->inTransaction(function () use ($workout): void {
            $this->persistUpdate($workout);
            $this->eventDispatcher->dispatch(new WorkoutUpdatedEvent($workout));
        });

        return $workout;
    }

    public function delete(WorkoutDataModel $workout): void
    {
        $event = new WorkoutDeletedEvent($workout->owner, $workout->movements(), $workout->finishedAt);

        $this->inTransaction(function () use ($workout, $event): void {
            $this->persistDelete($workout);
            $this->eventDispatcher->dispatch($event);
        });
    }
}
