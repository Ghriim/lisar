<?php

declare(strict_types=1);

namespace App\UseCase\Workout;

use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\DTO\DataModel\WorkoutDataModel;
use App\Domain\DTO\DataModel\WorkoutExerciseDataModel;
use App\Domain\DTO\Output\Workout\WorkoutDataOutput;
use App\Domain\Factory\OutputFactory\WorkoutOutputFactory;
use App\Domain\Gateway\Persister\WorkoutBlockPersisterGateway;
use App\Domain\Gateway\Persister\WorkoutExercisePersisterGateway;
use App\Domain\Gateway\Provider\UserProviderGateway;
use App\Domain\Gateway\Provider\WorkoutProviderGateway;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * Removing a movement from a workout, with its sets. A block never stays empty: removing its last
 * movement removes it too.
 */
final readonly class DeleteWorkoutExerciseUseCase implements UseCaseInterface
{
    public function __construct(
        private UserProviderGateway $userProviderGateway,
        private WorkoutProviderGateway $workoutProviderGateway,
        private WorkoutExercisePersisterGateway $workoutExercisePersisterGateway,
        private WorkoutBlockPersisterGateway $workoutBlockPersisterGateway,
        private WorkoutOutputFactory $outputFactory,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     */
    public function execute(int $ownerId, int $workoutId, int $exerciseId): WorkoutDataOutput
    {
        $owner = $this->userProviderGateway->findOneById($ownerId);
        if (null === $owner) {
            throw new DataModelNotFoundException(UserDataModel::class);
        }

        $workout = $this->workoutProviderGateway->findOneByIdForOwner($workoutId, $owner);
        if (null === $workout) {
            throw new DataModelNotFoundException(WorkoutDataModel::class);
        }

        $exercise = $workout->findExercise($exerciseId);
        if (null === $exercise) {
            throw new DataModelNotFoundException(WorkoutExerciseDataModel::class);
        }

        $block = $exercise->block;

        if (1 === $block->exercises->count()) {
            $this->workoutBlockPersisterGateway->delete($block);
            $workout->blocks->removeElement($block);
        } else {
            $this->workoutExercisePersisterGateway->delete($exercise);
            $block->exercises->removeElement($exercise);
        }

        return $this->outputFactory->buildOne($workout);
    }
}
