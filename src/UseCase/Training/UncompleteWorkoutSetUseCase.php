<?php

declare(strict_types=1);

namespace App\UseCase\Training;

use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutSetDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Output\Training\WorkoutDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\Training\WorkoutOutputFactory;
use App\Domain\Gateway\Persister\Training\WorkoutSetPersisterGateway;
use App\Domain\Gateway\Provider\Training\WorkoutProviderGateway;
use App\Domain\Gateway\Provider\Training\WorkoutSetProviderGateway;
use App\Domain\Validation\Validator\Training\UncompleteWorkoutSetValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * Unticking a set of the workout in progress — the undo of CompleteWorkoutSetUseCase, for the
 * mis-tap. Idempotent: a set not ticked stays so. Refused once the workout is finished, which
 * holds only sets that were done.
 */
final readonly class UncompleteWorkoutSetUseCase implements UseCaseInterface
{
    public function __construct(
        private UncompleteWorkoutSetValidator $validator,
        private WorkoutProviderGateway $workoutProviderGateway,
        private WorkoutSetProviderGateway $workoutSetProviderGateway,
        private WorkoutSetPersisterGateway $workoutSetPersisterGateway,
        private WorkoutOutputFactory $outputFactory,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(UserDataModel $currentUser, int $workoutId, int $setId): WorkoutDataOutput
    {
        $workout = $this->workoutProviderGateway->findOneById($workoutId);
        if (null === $workout) {
            throw new DataModelNotFoundException(WorkoutDataModel::class);
        }

        $set = $this->workoutSetProviderGateway->findOneById($setId);
        if (null === $set) {
            throw new DataModelNotFoundException(WorkoutSetDataModel::class);
        }

        $this->validator->validate($currentUser, $workout, $set);

        if (false !== $set->isComplete) {
            $set->isComplete = false;
            $this->workoutSetPersisterGateway->update($set);
        }

        return $this->outputFactory->buildOne($workout);
    }
}
