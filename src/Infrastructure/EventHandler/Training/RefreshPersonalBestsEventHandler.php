<?php

declare(strict_types=1);

namespace App\Infrastructure\EventHandler\Training;

use App\Domain\DTO\DataModel\Training\MovementDataModel;
use App\Domain\DTO\DataModel\Training\PersonalBestDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutSummaryDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Event\EventInterface;
use App\Domain\DTO\Event\Training\WorkoutBlockDeletedEvent;
use App\Domain\DTO\Event\Training\WorkoutDeletedEvent;
use App\Domain\DTO\Event\Training\WorkoutExerciseDeletedEvent;
use App\Domain\DTO\Event\Training\WorkoutSetCreatedEvent;
use App\Domain\DTO\Event\Training\WorkoutSetDeletedEvent;
use App\Domain\DTO\Event\Training\WorkoutSetUpdatedEvent;
use App\Domain\DTO\Event\Training\WorkoutUpdatedEvent;
use App\Domain\Factory\DataModelFactory\Training\PersonalBestDataModelFactory;
use App\Domain\Factory\DataModelFactory\Training\WorkoutSummaryDataModelFactory;
use App\Domain\Gateway\Persister\Training\PersonalBestPersisterGateway;
use App\Domain\Gateway\Persister\Training\WorkoutSummaryPersisterGateway;
use App\Domain\Gateway\Provider\Training\PersonalBestProviderGateway;
use App\Domain\Gateway\Provider\Training\WorkoutProviderGateway;
use App\Domain\Gateway\Provider\Training\WorkoutSummaryProviderGateway;
use App\Infrastructure\EventHandler\EventHandlerInterface;
use LogicException;

/**
 * Rebuilds the personal bests a write may have moved: those of each movement it touched, for its
 * owner, and those of the owner's whole workouts. Rebuilt from the sets rather than adjusted, so
 * a set unticked, corrected or removed hands its records back to whoever held them before.
 *
 * A write that cannot move one is let go without a query: only a set done before or after the
 * write makes a record, and only finishing gives a workout its length. The events say which.
 *
 * The records of whole workouts are read off the workouts' summaries. The summary of the workout
 * written to is worked out again here, in memory, from what the request holds: its own handler
 * runs on the same events, and neither may count on running first.
 *
 * Then recounts, on their summaries, the records of every workout whose rows the rebuild wrote —
 * not only the one written to: a record beaten again hands the row of an older workout back, or
 * takes it away.
 */
final readonly class RefreshPersonalBestsEventHandler implements EventHandlerInterface
{
    public function __construct(
        private WorkoutProviderGateway $workoutProviderGateway,
        private PersonalBestProviderGateway $personalBestProviderGateway,
        private PersonalBestPersisterGateway $personalBestPersisterGateway,
        private PersonalBestDataModelFactory $personalBestDataModelFactory,
        private WorkoutSummaryProviderGateway $workoutSummaryProviderGateway,
        private WorkoutSummaryPersisterGateway $workoutSummaryPersisterGateway,
        private WorkoutSummaryDataModelFactory $workoutSummaryDataModelFactory,
    ) {
    }

    public static function getSupportedEvents(): array
    {
        return [
            WorkoutSetCreatedEvent::class,
            WorkoutSetUpdatedEvent::class,
            WorkoutSetDeletedEvent::class,
            WorkoutExerciseDeletedEvent::class,
            WorkoutBlockDeletedEvent::class,
            WorkoutDeletedEvent::class,
            WorkoutUpdatedEvent::class,
        ];
    }

    public function handle(EventInterface $event): void
    {
        $touched = $this->touched($event);
        if (null === $touched) {
            return;
        }

        [$owner, $movements, $emptied] = $touched;
        $movements = $this->eachOnce($movements);
        $summaries = $this->summariesOf($owner, $this->workoutOf($event));

        // What stands now, the records of whole workouts and those of the movements touched, in
        // one read; then only what differs from the rebuild is written, in one flush.
        $written = $this->personalBestPersisterGateway->replace(
            $this->personalBestProviderGateway->findPersonalBestsForRefreshEvent($owner, $movements),
            [...$this->rebuildMovements($owner, $movements), ...$this->personalBestDataModelFactory->buildSessionProgression($owner, $summaries)],
        );

        $workoutsById = [];
        foreach ($written as $row) {
            $workoutsById[(int) $row->workout->id] = $row->workout;
        }
        foreach ($emptied as $workout) {
            $workoutsById[(int) $workout->id] = $workout;
        }

        if ([] !== $workoutsById) {
            $this->recount(array_values($workoutsById), $summaries);
        }
    }

    /**
     * Who the write touched, which of their movements, and the workouts to recount whatever the
     * rebuild writes; null when it cannot move a record.
     *
     * A set removed takes its rows with it, through the database, before the rebuild reads what
     * is left: its workout may have lost records the rebuild never sees go.
     *
     * @return array{UserDataModel, list<MovementDataModel>, list<WorkoutDataModel>}|null
     */
    private function touched(EventInterface $event): ?array
    {
        return match (true) {
            $event instanceof WorkoutSetCreatedEvent => true === $event->set->isComplete
                ? [$event->set->exercise->block->workout->owner, [$event->set->exercise->movement], []]
                : null,
            $event instanceof WorkoutSetUpdatedEvent => true === $event->wasCompleteBefore || true === $event->set->isComplete
                ? [$event->set->exercise->block->workout->owner, [$event->set->exercise->movement], []]
                : null,
            $event instanceof WorkoutSetDeletedEvent => true === $event->wasComplete ? [$event->workout->owner, [$event->movement], [$event->workout]] : null,
            $event instanceof WorkoutExerciseDeletedEvent => true === $event->heldCompleteSets ? [$event->workout->owner, [$event->movement], [$event->workout]] : null,
            $event instanceof WorkoutBlockDeletedEvent => true === $event->heldCompleteSets ? [$event->workout->owner, $event->movements, [$event->workout]] : null,
            // A finished workout held its length, even without a done set. Its summary went with it.
            $event instanceof WorkoutDeletedEvent => true === $event->heldCompleteSets || null !== $event->finishedAt ? [$event->owner, $event->movements, []] : null,
            // Finishing gives a workout its length; nothing else about it moves a record.
            $event instanceof WorkoutUpdatedEvent => true === $event->finishes ? [$event->workout->owner, [], []] : null,
            default => throw new LogicException(sprintf('%s does not handle %s.', self::class, $event::class)),
        };
    }

    /**
     * Each movement once, however the event names them: rebuilt twice, its rows would double.
     *
     * @param list<MovementDataModel> $movements
     *
     * @return list<MovementDataModel>
     */
    private function eachOnce(array $movements): array
    {
        $movementsById = [];
        foreach ($movements as $movement) {
            $movementsById[(int) $movement->id] = $movement;
        }

        return array_values($movementsById);
    }

    /**
     * Every movement touched at once: their sets read in one query, sorted out per movement here.
     *
     * @param list<MovementDataModel> $movements
     *
     * @return list<PersonalBestDataModel>
     */
    private function rebuildMovements(UserDataModel $owner, array $movements): array
    {
        if ([] === $movements) {
            return [];
        }

        $setsByMovementId = [];
        foreach ($this->workoutProviderGateway->findSetsCountingForPersonalBests($owner, $movements) as $set) {
            $setsByMovementId[(int) $set->exercise->movement->id][] = $set;
        }

        $rebuilt = [];
        foreach ($movements as $movement) {
            $rebuilt = [
                ...$rebuilt,
                ...$this->personalBestDataModelFactory->buildMovementProgression($owner, $movement, $setsByMovementId[(int) $movement->id] ?? []),
            ];
        }

        return $rebuilt;
    }

    /** The workout whose sets the write moved, still standing; null when none is. */
    private function workoutOf(EventInterface $event): ?WorkoutDataModel
    {
        return match (true) {
            $event instanceof WorkoutSetCreatedEvent, $event instanceof WorkoutSetUpdatedEvent => $event->set->exercise->block->workout,
            $event instanceof WorkoutSetDeletedEvent, $event instanceof WorkoutExerciseDeletedEvent, $event instanceof WorkoutBlockDeletedEvent => $event->workout,
            default => null,
        };
    }

    /**
     * The owner's summaries, oldest workout first, that of the workout written to worked out
     * again from what the request holds.
     *
     * @return list<WorkoutSummaryDataModel>
     */
    private function summariesOf(UserDataModel $owner, ?WorkoutDataModel $written): array
    {
        $summaries = $this->workoutSummaryProviderGateway->findAllForOwner($owner);

        foreach ($summaries as $summary) {
            if ($summary->workout === $written) {
                $this->workoutSummaryDataModelFactory->refresh($summary);
            }
        }

        return $summaries;
    }

    /**
     * @param list<WorkoutDataModel>        $workouts
     * @param list<WorkoutSummaryDataModel> $summaries the owner's, already read
     */
    private function recount(array $workouts, array $summaries): void
    {
        $counts = $this->personalBestProviderGateway->countByWorkout($workouts);

        $summariesByWorkoutId = [];
        foreach ($summaries as $summary) {
            $summariesByWorkoutId[(int) $summary->workout->id] = $summary;
        }

        $moved = [];
        foreach ($workouts as $workout) {
            $summary = $summariesByWorkoutId[(int) $workout->id] ?? null;
            if (null === $summary) {
                continue;
            }

            $count = $counts[(int) $workout->id] ?? 0;
            if ($count !== $summary->personalBestCount) {
                $summary->personalBestCount = $count;
                $moved[] = $summary;
            }
        }

        if ([] !== $moved) {
            $this->workoutSummaryPersisterGateway->updateMany($moved);
        }
    }
}
