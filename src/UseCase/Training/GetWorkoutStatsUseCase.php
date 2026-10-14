<?php

declare(strict_types=1);

namespace App\UseCase\Training;

use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Output\Training\WorkoutStatsDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\Training\WorkoutStatsOutputFactory;
use App\Domain\Gateway\Provider\Training\PersonalBestProviderGateway;
use App\Domain\Gateway\Provider\Training\WorkoutProviderGateway;
use App\Domain\Gateway\Provider\Training\WorkoutSummaryProviderGateway;
use App\Domain\Validation\Validator\Training\GetWorkoutStatsValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * What one of the owner's workouts amounts to: sets, load, time, distance, and the muscles it fell
 * on. Read on the screen that closes a workout, and on a finished one's page.
 */
final readonly class GetWorkoutStatsUseCase implements UseCaseInterface
{
    public function __construct(
        private GetWorkoutStatsValidator $validator,
        private WorkoutProviderGateway $workoutProviderGateway,
        private PersonalBestProviderGateway $personalBestProviderGateway,
        private WorkoutSummaryProviderGateway $workoutSummaryProviderGateway,
        private WorkoutStatsOutputFactory $outputFactory,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(UserDataModel $currentUser, int $workoutId): WorkoutStatsDataOutput
    {
        $workout = $this->workoutProviderGateway->findOneById($workoutId);
        if (null === $workout) {
            throw new DataModelNotFoundException(WorkoutDataModel::class);
        }

        $this->validator->validate($currentUser, $workout);

        return $this->outputFactory->buildOne(
            $workout,
            $this->workoutSummaryProviderGateway->findOneForWorkout($workout),
            $this->personalBestProviderGateway->findAllForWorkout($workout),
        );
    }
}
