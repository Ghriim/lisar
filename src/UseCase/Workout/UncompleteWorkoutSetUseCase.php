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
 * Unticking a set of the workout in progress — the undo of CompleteWorkoutSetUseCase, for the
 * mis-tap. Idempotent: a set not ticked stays so. Refused once the workout is finished, which
 * holds only sets that were done.
 */
final readonly class UncompleteWorkoutSetUseCase implements UseCaseInterface
{
    public const string ERROR_CODE = 'uncomplete_workout_set_invalid';

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

        if (false !== $set->isComplete) {
            $set->isComplete = false;
            $this->workoutSetPersisterGateway->update($set);
        }

        return $this->outputFactory->buildOne($workout);
    }
}
