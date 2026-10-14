<?php

declare(strict_types=1);

namespace App\UseCase\Training;

use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutExerciseDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\UpdateWorkoutExerciseDataInput;
use App\Domain\DTO\Output\Training\WorkoutDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\Training\WorkoutOutputFactory;
use App\Domain\Gateway\Persister\Training\WorkoutExercisePersisterGateway;
use App\Domain\Gateway\Provider\Training\WorkoutProviderGateway;
use App\Domain\Validation\Validator\Training\UpdateWorkoutExerciseValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * The note on a movement as done in one workout, and the rest planned after each of its sets.
 */
final readonly class UpdateWorkoutExerciseUseCase implements UseCaseInterface
{
    public function __construct(
        private UpdateWorkoutExerciseValidator $validator,
        private WorkoutProviderGateway $workoutProviderGateway,
        private WorkoutExercisePersisterGateway $workoutExercisePersisterGateway,
        private WorkoutOutputFactory $outputFactory,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(UserDataModel $currentUser, int $workoutId, int $exerciseId, UpdateWorkoutExerciseDataInput $input): WorkoutDataOutput
    {
        $workout = $this->workoutProviderGateway->findOneById($workoutId);
        if (null === $workout) {
            throw new DataModelNotFoundException(WorkoutDataModel::class);
        }

        $exercise = $workout->findExercise($exerciseId);
        if (null === $exercise) {
            throw new DataModelNotFoundException(WorkoutExerciseDataModel::class);
        }

        $this->validator->validate($currentUser, $workout, $input);

        $exercise->note = $input->getNote();
        $exercise->restInSeconds = $input->restInSeconds;
        $this->workoutExercisePersisterGateway->update($exercise);

        return $this->outputFactory->buildOne($workout);
    }
}
