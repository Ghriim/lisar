<?php

declare(strict_types=1);

namespace App\UseCase\Workout;

use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\DTO\DataModel\WorkoutDataModel;
use App\Domain\DTO\Output\Workout\WorkoutDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\WorkoutOutputFactory;
use App\Domain\Gateway\Persister\WorkoutPersisterGateway;
use App\Domain\Gateway\Provider\UserProviderGateway;
use App\Domain\Gateway\Provider\WorkoutProviderGateway;
use App\Domain\Tracking\DayClock;
use App\Domain\Validation\Constraint\Workout\WorkoutFinishableConstraint;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * Finishing the workout in progress, now. A workout without a single set is refused — abandoning
 * it is the way out — and so is one with a set not ticked as done. The habits fed by the workout
 * tracker hear of it.
 */
final readonly class FinishWorkoutUseCase implements UseCaseInterface
{
    public const string ERROR_CODE = 'finish_workout_invalid';

    public function __construct(
        private UserProviderGateway $userProviderGateway,
        private WorkoutProviderGateway $workoutProviderGateway,
        private WorkoutPersisterGateway $workoutPersisterGateway,
        private SyncWorkoutHabitsUseCase $syncWorkoutHabits,
        private WorkoutOutputFactory $outputFactory,
        private DayClock $clock,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(int $ownerId, int $workoutId): WorkoutDataOutput
    {
        $owner = $this->userProviderGateway->findOneById($ownerId);
        if (null === $owner) {
            throw new DataModelNotFoundException(UserDataModel::class);
        }

        $workout = $this->workoutProviderGateway->findOneByIdForOwner($workoutId, $owner);
        if (null === $workout) {
            throw new DataModelNotFoundException(WorkoutDataModel::class);
        }

        if (false === $workout->isInProgress()) {
            // Already finished: the caller wanted it done and it is. Its moment is not rewritten.
            return $this->outputFactory->buildOne($workout);
        }

        $violations = WorkoutFinishableConstraint::validate($workout);
        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }

        $finishedAt = $this->clock->now();
        $workout->finishedAt = $finishedAt;
        $this->workoutPersisterGateway->update($workout);

        $this->syncWorkoutHabits->execute($owner, $finishedAt);

        return $this->outputFactory->buildOne($workout);
    }
}
