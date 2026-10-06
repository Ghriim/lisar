<?php

declare(strict_types=1);

namespace App\UseCase\Workout;

use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\DTO\DataModel\WorkoutDataModel;
use App\Domain\DTO\DataModel\WorkoutSetDataModel;
use App\Domain\DTO\Output\Workout\WorkoutDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\WorkoutOutputFactory;
use App\Domain\Gateway\Persister\WorkoutSetPersisterGateway;
use App\Domain\Gateway\Provider\UserProviderGateway;
use App\Domain\Gateway\Provider\WorkoutProviderGateway;
use App\Domain\Validation\Constraint\Workout\WorkoutInProgressConstraint;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * Ticking a set of the workout in progress as done. Idempotent: a set already ticked stays so.
 * Once the workout is finished there is nothing to tick — every set in it was done.
 */
final readonly class CompleteWorkoutSetUseCase implements UseCaseInterface
{
    public const string ERROR_CODE = 'complete_workout_set_invalid';

    public function __construct(
        private UserProviderGateway $userProviderGateway,
        private WorkoutProviderGateway $workoutProviderGateway,
        private WorkoutSetPersisterGateway $workoutSetPersisterGateway,
        private WorkoutOutputFactory $outputFactory,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(int $ownerId, int $workoutId, int $setId): WorkoutDataOutput
    {
        $owner = $this->userProviderGateway->findOneById($ownerId);
        if (null === $owner) {
            throw new DataModelNotFoundException(UserDataModel::class);
        }

        $workout = $this->workoutProviderGateway->findOneByIdForOwner($workoutId, $owner);
        if (null === $workout) {
            throw new DataModelNotFoundException(WorkoutDataModel::class);
        }

        $set = $workout->findSet($setId);
        if (null === $set) {
            throw new DataModelNotFoundException(WorkoutSetDataModel::class);
        }

        $violations = WorkoutInProgressConstraint::validate($workout);
        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }

        if (true !== $set->isComplete) {
            $set->isComplete = true;
            $this->workoutSetPersisterGateway->update($set);
        }

        return $this->outputFactory->buildOne($workout);
    }
}
