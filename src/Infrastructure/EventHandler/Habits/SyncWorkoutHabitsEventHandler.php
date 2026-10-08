<?php

declare(strict_types=1);

namespace App\Infrastructure\EventHandler\Habits;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Event\EventInterface;
use App\Domain\DTO\Event\Training\WorkoutDeletedEvent;
use App\Domain\DTO\Event\Training\WorkoutUpdatedEvent;
use App\Domain\Factory\DataModelFactory\Habits\HabitEntryDataModelFactory;
use App\Domain\Gateway\Persister\Habits\HabitEntryPersisterGateway;
use App\Domain\Gateway\Provider\Habits\HabitEntryProviderGateway;
use App\Domain\Gateway\Provider\Habits\HabitSubscriptionProviderGateway;
use App\Domain\Gateway\Provider\Training\WorkoutProviderGateway;
use App\Domain\Registry\Habits\HabitTrackerRegistry;
use App\Domain\Tracking\DayClock;
use DateTimeImmutable;
use LogicException;

/**
 * The workout tracker's figure is how many workouts the owner finished on a day. It moves when
 * one is finished — a workout update — or when a finished one is deleted; one in progress counts
 * for nothing, so writes on it move nothing.
 */
final readonly class SyncWorkoutHabitsEventHandler extends AbstractTrackerHabitsEventHandler
{
    public function __construct(
        HabitSubscriptionProviderGateway $habitSubscriptionProviderGateway,
        HabitEntryProviderGateway $habitEntryProviderGateway,
        HabitEntryPersisterGateway $habitEntryPersisterGateway,
        HabitEntryDataModelFactory $habitEntryDataModelFactory,
        DayClock $clock,
        private WorkoutProviderGateway $workoutProviderGateway,
    ) {
        parent::__construct($habitSubscriptionProviderGateway, $habitEntryProviderGateway, $habitEntryPersisterGateway, $habitEntryDataModelFactory, $clock);
    }

    public static function getSupportedEvents(): array
    {
        return [WorkoutUpdatedEvent::class, WorkoutDeletedEvent::class];
    }

    public function handle(EventInterface $event): void
    {
        [$owner, $finishedAt] = match (true) {
            $event instanceof WorkoutUpdatedEvent => [$event->workout->owner, $event->workout->finishedAt],
            $event instanceof WorkoutDeletedEvent => [$event->owner, $event->finishedAt],
            default => throw new LogicException(sprintf('%s does not handle %s.', self::class, $event::class)),
        };

        if (null !== $finishedAt) {
            $this->recount($owner, $finishedAt);
        }
    }

    /** `$finishedAt` is any moment of the day to recount: the day it falls on is the one. */
    private function recount(UserDataModel $owner, DateTimeImmutable $finishedAt): void
    {
        $day = $this->clock->inDisplayZone($finishedAt)->setTime(0, 0);

        $count = $this->workoutProviderGateway->countFinishedForOwnerBetween(
            $owner,
            $this->clock->asStoredInstant($day),
            $this->clock->asStoredInstant($day->modify('+1 day')),
        );

        $this->keep($owner, HabitTrackerRegistry::WORKOUT, $count, $day);
    }
}
