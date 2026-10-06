<?php

declare(strict_types=1);

namespace App\UseCase\Workout;

use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\DTO\Output\Workout\WorkoutDataOutput;
use App\Domain\Factory\OutputFactory\WorkoutOutputFactory;
use App\Domain\Gateway\Provider\UserProviderGateway;
use App\Domain\Gateway\Provider\WorkoutProviderGateway;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/** The workout in progress, whole, or null when there is none. */
final readonly class GetCurrentWorkoutUseCase implements UseCaseInterface
{
    public function __construct(
        private UserProviderGateway $userProviderGateway,
        private WorkoutProviderGateway $workoutProviderGateway,
        private WorkoutOutputFactory $outputFactory,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     */
    public function execute(int $ownerId): ?WorkoutDataOutput
    {
        $owner = $this->userProviderGateway->findOneById($ownerId);
        if (null === $owner) {
            throw new DataModelNotFoundException(UserDataModel::class);
        }

        $workout = $this->workoutProviderGateway->findOneInProgressForOwner($owner);

        return null === $workout ? null : $this->outputFactory->buildOne($workout);
    }
}
