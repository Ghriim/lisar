<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister\Training;

use App\Domain\DTO\DataModel\Training\WorkoutBlockDataModel;
use App\Domain\DTO\Event\Training\WorkoutBlockDeletedEvent;
use App\Domain\Event\EventDispatcherInterface;
use App\Domain\Gateway\Persister\Training\WorkoutBlockPersisterGateway;
use App\Infrastructure\Persister\AbstractBaseMysqlPersister;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @extends AbstractBaseMysqlPersister<WorkoutBlockDataModel>
 */
final class WorkoutBlockPersister extends AbstractBaseMysqlPersister implements WorkoutBlockPersisterGateway
{
    public function __construct(
        EntityManagerInterface $entityManager,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
        parent::__construct($entityManager);
    }

    public function create(WorkoutBlockDataModel $workoutBlock): WorkoutBlockDataModel
    {
        $this->persistCreate($workoutBlock);
        if (false === $workoutBlock->workout->blocks->contains($workoutBlock)) {
            $workoutBlock->workout->blocks->add($workoutBlock);
        }

        return $workoutBlock;
    }

    public function update(WorkoutBlockDataModel $workoutBlock): WorkoutBlockDataModel
    {
        return $this->persistUpdate($workoutBlock);
    }

    public function delete(WorkoutBlockDataModel $workoutBlock): void
    {
        $workout = $workoutBlock->workout;
        $heldCountingSets = false;
        foreach ($workoutBlock->exercises as $exercise) {
            foreach ($exercise->sets as $set) {
                $heldCountingSets = $heldCountingSets || $set->countsForPersonalBests();
            }
        }
        $event = new WorkoutBlockDeletedEvent($workout, $workoutBlock->movements(), $heldCountingSets);

        $this->inTransaction(function () use ($workoutBlock, $workout, $event): void {
            $this->persistDelete($workoutBlock);
            $workout->blocks->removeElement($workoutBlock);
            $this->eventDispatcher->dispatch($event);
        });
    }
}
