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
        return $this->persistAndStampCreate($workoutBlock);
    }

    public function update(WorkoutBlockDataModel $workoutBlock): WorkoutBlockDataModel
    {
        return $this->persistAndStampUpdate($workoutBlock);
    }

    public function delete(WorkoutBlockDataModel $workoutBlock): void
    {
        $event = new WorkoutBlockDeletedEvent($workoutBlock->workout->owner, $workoutBlock->movements());

        $this->inTransaction(function () use ($workoutBlock, $event): void {
            $this->persistDelete($workoutBlock);
            $this->eventDispatcher->dispatch($event);
        });
    }
}
