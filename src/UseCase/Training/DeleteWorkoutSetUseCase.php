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
use App\Domain\Validation\Validator\Training\DeleteWorkoutSetValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * Removing a set. The movement stays in the workout, even with no set left.
 */
final readonly class DeleteWorkoutSetUseCase implements UseCaseInterface
{
    public function __construct(
        private DeleteWorkoutSetValidator $validator,
        private WorkoutProviderGateway $workoutProviderGateway,
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

        $set = $workout->findSet($setId);
        if (null === $set) {
            throw new DataModelNotFoundException(WorkoutSetDataModel::class);
        }

        $this->validator->validate($currentUser, $workout);

        $this->workoutSetPersisterGateway->delete($set);

        return $this->outputFactory->buildOne($workout);
    }
}
