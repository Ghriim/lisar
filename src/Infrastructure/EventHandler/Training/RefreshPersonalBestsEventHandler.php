<?php

declare(strict_types=1);

namespace App\Infrastructure\EventHandler\Training;

use App\Domain\DTO\DataModel\Training\MovementDataModel;
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
use App\Domain\Gateway\Provider\Training\PersonalBestProviderGateway;
use App\Domain\Gateway\Provider\Training\WorkoutProviderGateway;
use App\Infrastructure\EventHandler\EventHandlerInterface;
use LogicException;

/**
 * Rebuilds the personal bests a write may have moved: those of each movement it touched, for its
 * owner, and those of the owner's whole workouts. Rebuilt from the sets rather than adjusted, so
 * a set unticked, corrected or removed hands its records back to whoever held them before.
 */
final readonly class RefreshPersonalBestsEventHandler implements EventHandlerInterface
{
    public function __construct(
        private WorkoutProviderGateway $workoutProviderGateway,
        private PersonalBestProviderGateway $personalBestProviderGateway,
        private PersonalBestPersisterGateway $personalBestPersisterGateway,
        private PersonalBestDataModelFactory $personalBestDataModelFactory,
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
        foreach ($this->touched($event) as [$owner, $movements]) {
            foreach ($movements as $movement) {
                $this->refreshMovement($owner, $movement);
            }
            $this->refreshSessions($owner);
        }
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
            $event instanceof WorkoutSetDeletedEvent, $event instanceof WorkoutExerciseDeletedEvent => [[$event->owner, [$event->movement]]],
            $event instanceof WorkoutBlockDeletedEvent, $event instanceof WorkoutDeletedEvent => [[$event->owner, $event->movements]],
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

    private function refreshMovement(UserDataModel $owner, MovementDataModel $movement): void
    {
        $this->personalBestPersisterGateway->replace(
            $this->personalBestProviderGateway->findAllForOwnerAndMovement($owner, $movement),
            $this->personalBestDataModelFactory->buildMovementProgression(
                $owner,
                $movement,
                $this->workoutProviderGateway->findSetsCountingForPersonalBests($owner, $movement),
            ),
        );
    }

    private function refreshSessions(UserDataModel $owner): void
    {
        $this->personalBestPersisterGateway->replace(
            $this->personalBestProviderGateway->findAllForOwnerAndMovement($owner, null),
            $this->personalBestDataModelFactory->buildSessionProgression(
                $owner,
                $this->workoutProviderGateway->findTalliesForOwner($owner),
            ),
        );
    }
}
