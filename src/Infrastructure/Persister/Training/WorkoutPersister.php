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
        // Read before the flush forgets it: a finished workout's moment is never rewritten, so only
        // the write giving it one finishes it.
        $finishes = null === $this->originalValueOf($workout, 'finishedAt') && null !== $workout->finishedAt;

        $this->inTransaction(function () use ($workout, $finishes): void {
            $this->persistUpdate($workout);
            $this->eventDispatcher->dispatch(new WorkoutUpdatedEvent($workout, $finishes));
        });

        return $workout;
    }

    public function delete(WorkoutDataModel $workout): void
    {
        $heldCountingSets = false;
        foreach ($workout->blocks as $block) {
            foreach ($block->exercises as $exercise) {
                foreach ($exercise->sets as $set) {
                    $heldCountingSets = $heldCountingSets || $set->countsForPersonalBests();
                }
            }
        }
        $event = new WorkoutDeletedEvent($workout->owner, $workout->movements(), $workout->finishedAt, $heldCountingSets);

        $this->inTransaction(function () use ($workout, $event): void {
            $this->persistDelete($workout);
            $this->eventDispatcher->dispatch($event);
        });
    }
}
