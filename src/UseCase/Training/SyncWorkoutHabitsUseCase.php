<?php

declare(strict_types=1);

namespace App\UseCase\Training;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Gateway\Provider\Training\WorkoutProviderGateway;
use App\Domain\Registry\Habits\HabitTrackerRegistry;
use App\Domain\Tracking\DayClock;
use App\UseCase\Habits\SyncTrackerHabitsUseCase;
use App\UseCase\UseCaseInterface;
use DateTimeImmutable;

/**
 * Announcing the workout tracker's figure for one day — how many workouts the owner finished on
 * it — to the habits it feeds. Called whenever that figure may have moved: a workout finished, a
 * finished one deleted.
 */
final readonly class SyncWorkoutHabitsUseCase implements UseCaseInterface
{
    public function __construct(
        private WorkoutProviderGateway $workoutProviderGateway,
        private SyncTrackerHabitsUseCase $syncTrackerHabits,
        private DayClock $clock,
    ) {
    }

    /** `$finishedAt` is any moment of the day to recount: the day it falls on is the one. */
    public function execute(UserDataModel $owner, DateTimeImmutable $finishedAt): void
    {
        $day = $this->clock->inDisplayZone($finishedAt)->setTime(0, 0);

        $count = $this->workoutProviderGateway->countFinishedForOwnerBetween(
            $owner,
            $this->clock->asStoredInstant($day),
            $this->clock->asStoredInstant($day->modify('+1 day')),
        );

        $this->syncTrackerHabits->execute((int) $owner->id, HabitTrackerRegistry::WORKOUT, $count, $day);
    }
}
