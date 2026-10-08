<?php

declare(strict_types=1);

namespace App\Infrastructure\EventHandler\Training;

use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\Event\EventInterface;
use App\Domain\DTO\Event\Training\WorkoutBlockDeletedEvent;
use App\Domain\DTO\Event\Training\WorkoutCreatedEvent;
use App\Domain\DTO\Event\Training\WorkoutExerciseDeletedEvent;
use App\Domain\DTO\Event\Training\WorkoutSetCreatedEvent;
use App\Domain\DTO\Event\Training\WorkoutSetDeletedEvent;
use App\Domain\DTO\Event\Training\WorkoutSetUpdatedEvent;
use App\Domain\Factory\DataModelFactory\Training\WorkoutSummaryDataModelFactory;
use App\Domain\Gateway\Persister\Training\WorkoutSummaryPersisterGateway;
use App\Domain\Gateway\Provider\Training\WorkoutSummaryProviderGateway;
use App\Infrastructure\EventHandler\EventHandlerInterface;
use LogicException;

/**
 * Creates a workout's summary with the workout, and works its totals out again each time one of
 * its sets moves. Its record count is the records' rebuild's to move: see
 * `RefreshPersonalBestsEventHandler`.
 */
final readonly class RefreshWorkoutSummaryEventHandler implements EventHandlerInterface
{
    public function __construct(
        private WorkoutSummaryProviderGateway $workoutSummaryProviderGateway,
        private WorkoutSummaryPersisterGateway $workoutSummaryPersisterGateway,
        private WorkoutSummaryDataModelFactory $workoutSummaryDataModelFactory,
    ) {
    }

    public static function getSupportedEvents(): array
    {
        return [
            WorkoutCreatedEvent::class,
            WorkoutSetCreatedEvent::class,
            WorkoutSetUpdatedEvent::class,
            WorkoutSetDeletedEvent::class,
            WorkoutExerciseDeletedEvent::class,
            WorkoutBlockDeletedEvent::class,
        ];
    }

    public function handle(EventInterface $event): void
    {
        $workout = $this->workoutOf($event);
        $summary = $this->workoutSummaryProviderGateway->findOneForWorkout($workout);

        if (null === $summary) {
            $this->workoutSummaryPersisterGateway->create($this->workoutSummaryDataModelFactory->build($workout));

            return;
        }

        $this->workoutSummaryDataModelFactory->refresh($summary);
        $this->workoutSummaryPersisterGateway->updateMany([$summary]);
    }

    private function workoutOf(EventInterface $event): WorkoutDataModel
    {
        return match (true) {
            $event instanceof WorkoutCreatedEvent => $event->workout,
            $event instanceof WorkoutSetCreatedEvent, $event instanceof WorkoutSetUpdatedEvent => $event->set->exercise->block->workout,
            $event instanceof WorkoutSetDeletedEvent, $event instanceof WorkoutExerciseDeletedEvent, $event instanceof WorkoutBlockDeletedEvent => $event->workout,
            default => throw new LogicException(sprintf('%s does not handle %s.', self::class, $event::class)),
        };
    }
}
