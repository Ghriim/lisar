<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister\Training;

use App\Domain\DTO\DataModel\Training\WorkoutBlockDataModel;
use App\Domain\DTO\Event\Training\WorkoutBlockDeletedEvent;
use App\Domain\Event\EventDispatcherInterface;
use App\Domain\Gateway\Persister\Training\WorkoutBlockPersisterGateway;
use App\Infrastructure\Persister\AbstractBaseMysqlPersister;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;

/**
 * @extends AbstractBaseMysqlPersister<WorkoutBlockDataModel>
 */
final class WorkoutBlockPersister extends AbstractBaseMysqlPersister implements WorkoutBlockPersisterGateway
{
    public function __construct(
        EntityManagerInterface $entityManager,
        ClockInterface $clock,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
        parent::__construct($entityManager, $clock);
    }

    public function create(WorkoutBlockDataModel $workoutBlock): WorkoutBlockDataModel
    {
        $this->persistAndStampCreate($workoutBlock);
        if (false === $workoutBlock->workout->blocks->contains($workoutBlock)) {
            $workoutBlock->workout->blocks->add($workoutBlock);
        }

        return $workoutBlock;
    }

    public function update(WorkoutBlockDataModel $workoutBlock): WorkoutBlockDataModel
    {
        return $this->persistAndStampUpdate($workoutBlock);
    }

    public function delete(WorkoutBlockDataModel $workoutBlock): void
    {
        $workout = $workoutBlock->workout;
        $event = new WorkoutBlockDeletedEvent($workout, $workoutBlock->movements());

        $this->inTransaction(function () use ($workoutBlock, $workout, $event): void {
            $this->persistDelete($workoutBlock);
            $workout->blocks->removeElement($workoutBlock);
            $this->eventDispatcher->dispatch($event);
        });
    }
}
