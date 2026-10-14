<?php

declare(strict_types=1);

namespace App\Infrastructure\EventHandler\Training;

use App\Domain\DTO\DataModel\Training\MovementDataModel;
use App\Domain\DTO\DataModel\Training\PersonalBestDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Event\EventInterface;
use App\Domain\DTO\Event\Training\SetTypeUpdatedEvent;
use App\Domain\DTO\Event\Training\WorkoutBlockDeletedEvent;
use App\Domain\DTO\Event\Training\WorkoutDeletedEvent;
use App\Domain\DTO\Event\Training\WorkoutExerciseDeletedEvent;
use App\Domain\DTO\Event\Training\WorkoutSetCreatedEvent;
use App\Domain\DTO\Event\Training\WorkoutSetDeletedEvent;
use App\Domain\DTO\Event\Training\WorkoutSetUpdatedEvent;
use App\Domain\DTO\Event\Training\WorkoutUpdatedEvent;
use App\Domain\Factory\DataModelFactory\Training\PersonalBestDataModelFactory;
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
 * Then recounts the records of every workout that lost or gained one — not only the one written
 * to: a record beaten again hands the row of an older workout back, or takes it away — on their
 * summaries.
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
            SetTypeUpdatedEvent::class,
        ];
    }

    public function handle(EventInterface $event): void
    {
        /** @var array<int, WorkoutDataModel> $workouts */
        $workouts = [];
        foreach ($this->touched($event) as [$owner, $movements]) {
            $workouts += $this->refreshMovements($owner, $movements);
            $workouts += $this->refreshSessions($owner);
        }

        $this->recount(array_values($workouts));
    }

    /**
     * Who the write touched, and which of their movements.
     *
     * @return list<array{UserDataModel, list<MovementDataModel>}>
     */
    private function touched(EventInterface $event): array
    {
        return match (true) {
            $event instanceof WorkoutSetCreatedEvent, $event instanceof WorkoutSetUpdatedEvent => [[
                $event->set->exercise->block->workout->owner,
                [$event->set->exercise->movement],
            ]],
            $event instanceof WorkoutSetDeletedEvent, $event instanceof WorkoutExerciseDeletedEvent => [[$event->workout->owner, [$event->movement]]],
            $event instanceof WorkoutBlockDeletedEvent => [[$event->workout->owner, $event->movements]],
            $event instanceof WorkoutDeletedEvent => [[$event->owner, $event->movements]],
            // Finishing gives a workout its length; nothing else about it moves a movement's record.
            $event instanceof WorkoutUpdatedEvent => [[$event->workout->owner, []]],
            $event instanceof SetTypeUpdatedEvent => true === $event->countsForPersonalBestsChanged ? $this->carrying($event) : [],
            default => throw new LogicException(sprintf('%s does not handle %s.', self::class, $event::class)),
        };
    }

    /**
     * Everyone whose sets carry that type, with the movements they carry it on.
     *
     * @return list<array{UserDataModel, list<MovementDataModel>}>
     */
    private function carrying(SetTypeUpdatedEvent $event): array
    {
        /** @var array<int, array{UserDataModel, array<int, MovementDataModel>}> $byOwner */
        $byOwner = [];
        foreach ($this->workoutProviderGateway->findExercisesWithSetType($event->setType) as $exercise) {
            $owner = $exercise->block->workout->owner;
            $byOwner[(int) $owner->id][0] = $owner;
            $byOwner[(int) $owner->id][1][(int) $exercise->movement->id] = $exercise->movement;
        }

        $touched = [];
        foreach ($byOwner as [$owner, $movements]) {
            $touched[] = [$owner, array_values($movements)];
        }

        return $touched;
    }

    /**
     * Every movement touched at once: their rows and their sets read in one query each, the sets
     * sorted out per movement here.
     *
     * @param list<MovementDataModel> $movements
     *
     * @return array<int, WorkoutDataModel> the workouts holding a row, before or after, by id
     */
    private function refreshMovements(UserDataModel $owner, array $movements): array
    {
        // Each movement rebuilt once, however the event names them: twice would double its rows.
        $movementsById = [];
        foreach ($movements as $movement) {
            $movementsById[(int) $movement->id] = $movement;
        }

        if ([] === $movementsById) {
            return [];
        }

        $movements = array_values($movementsById);

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

        return $this->replace($this->personalBestProviderGateway->findAllForOwnerAndMovements($owner, $movements), $rebuilt);
    }

    /** @return array<int, WorkoutDataModel> the workouts holding a row, before or after, by id */
    private function refreshSessions(UserDataModel $owner): array
    {
        return $this->replace(
            $this->personalBestProviderGateway->findAllSessionsForOwner($owner),
            $this->personalBestDataModelFactory->buildSessionProgression(
                $owner,
                $this->workoutProviderGateway->findTalliesForOwner($owner),
            ),
        );
    }

    /**
     * @param list<PersonalBestDataModel> $previous
     * @param list<PersonalBestDataModel> $rebuilt
     *
     * @return array<int, WorkoutDataModel> the workouts holding a row, before or after, by id
     */
    private function replace(array $previous, array $rebuilt): array
    {
        $this->personalBestPersisterGateway->replace($previous, $rebuilt);

        $workouts = [];
        foreach ([...$previous, ...$rebuilt] as $row) {
            $workouts[(int) $row->workout->id] = $row->workout;
        }

        return $workouts;
    }

    /** @param list<WorkoutDataModel> $workouts */
    private function recount(array $workouts): void
    {
        $counts = $this->personalBestProviderGateway->countByWorkout($workouts);

        $moved = [];
        foreach ($this->workoutSummaryProviderGateway->findAllForWorkouts($workouts) as $workoutId => $summary) {
            $count = $counts[$workoutId] ?? 0;
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
