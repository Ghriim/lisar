<?php

declare(strict_types=1);

namespace App\UseCase\Training;

use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutExerciseDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Output\Training\WorkoutDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\Training\WorkoutOutputFactory;
use App\Domain\Gateway\Persister\Training\WorkoutBlockPersisterGateway;
use App\Domain\Gateway\Persister\Training\WorkoutExercisePersisterGateway;
use App\Domain\Gateway\Provider\Training\WorkoutProviderGateway;
use App\Domain\Validation\Validator\Training\DeleteWorkoutExerciseValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * Removing a movement from a workout, with its sets. A block never stays empty: removing its last
 * movement removes it too.
 */
final readonly class DeleteWorkoutExerciseUseCase implements UseCaseInterface
{
    public function __construct(
        private DeleteWorkoutExerciseValidator $validator,
        private WorkoutProviderGateway $workoutProviderGateway,
        private WorkoutExercisePersisterGateway $workoutExercisePersisterGateway,
        private WorkoutBlockPersisterGateway $workoutBlockPersisterGateway,
        private WorkoutOutputFactory $outputFactory,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(UserDataModel $currentUser, int $workoutId, int $exerciseId): WorkoutDataOutput
    {
        $workout = $this->workoutProviderGateway->findOneById($workoutId);
        if (null === $workout) {
            throw new DataModelNotFoundException(WorkoutDataModel::class);
        }

        $exercise = $workout->findExercise($exerciseId);
        if (null === $exercise) {
            throw new DataModelNotFoundException(WorkoutExerciseDataModel::class);
        }

        $this->validator->validate($currentUser, $workout);

        $block = $exercise->block;

        if (1 === $block->exercises->count()) {
            $this->workoutBlockPersisterGateway->delete($block);
        } else {
            $this->workoutExercisePersisterGateway->delete($exercise);
        }

        return $this->outputFactory->buildOne($workout);
    }
}
