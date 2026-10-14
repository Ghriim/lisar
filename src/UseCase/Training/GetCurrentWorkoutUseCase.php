<?php

declare(strict_types=1);

namespace App\UseCase\Training;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Output\Training\WorkoutDataOutput;
use App\Domain\Factory\OutputFactory\Training\WorkoutOutputFactory;
use App\Domain\Gateway\Provider\Training\WorkoutProviderGateway;
use App\Domain\Validation\Validator\Training\GetCurrentWorkoutValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/** The workout in progress, whole, or null when there is none. */
final readonly class GetCurrentWorkoutUseCase implements UseCaseInterface
{
    public function __construct(
        private GetCurrentWorkoutValidator $validator,
        private WorkoutProviderGateway $workoutProviderGateway,
        private WorkoutOutputFactory $outputFactory,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     */
    public function execute(UserDataModel $currentUser): ?WorkoutDataOutput
    {
        $this->validator->validate($currentUser);

        $workout = $this->workoutProviderGateway->findOneInProgressForOwner($currentUser);

        return null === $workout ? null : $this->outputFactory->buildOne($workout);
    }
}
