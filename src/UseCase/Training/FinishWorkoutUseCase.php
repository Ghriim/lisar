<?php

declare(strict_types=1);

namespace App\UseCase\Training;

use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Output\Training\WorkoutDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\Training\WorkoutOutputFactory;
use App\Domain\Gateway\Persister\Training\WorkoutPersisterGateway;
use App\Domain\Gateway\Provider\Training\WorkoutProviderGateway;
use App\Domain\Tracking\DayClock;
use App\Domain\Validation\Validator\Training\FinishWorkoutValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * Finishing the workout in progress, now. A workout without a single set is refused — abandoning
 * it is the way out — and so is one with a set not ticked as done. The habits fed by the workout
 * tracker hear of it.
 */
final readonly class FinishWorkoutUseCase implements UseCaseInterface
{
    public function __construct(
        private FinishWorkoutValidator $validator,
        private WorkoutProviderGateway $workoutProviderGateway,
        private WorkoutPersisterGateway $workoutPersisterGateway,
        private WorkoutOutputFactory $outputFactory,
        private DayClock $clock,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(UserDataModel $currentUser, int $workoutId): WorkoutDataOutput
    {
        $workout = $this->workoutProviderGateway->findOneById($workoutId);
        if (null === $workout) {
            throw new DataModelNotFoundException(WorkoutDataModel::class);
        }

        // Validated first, finished or not: a workout that is someone else's is not handed back either.
        $this->validator->validate($currentUser, $workout);

        if (false === $workout->isInProgress()) {
            // Already finished: the caller wanted it done and it is. Its moment is not rewritten.
            return $this->outputFactory->buildOne($workout);
        }

        $finishedAt = $this->clock->now();
        $workout->finishedAt = $finishedAt;
        // The habits watching workouts follow, on the persister's event.
        $this->workoutPersisterGateway->update($workout);

        return $this->outputFactory->buildOne($workout);
    }
}
