<?php

declare(strict_types=1);

namespace App\UseCase\Training;

use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutSetDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\UpdateWorkoutSetDataInput;
use App\Domain\DTO\Output\Training\WorkoutDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\Training\WorkoutOutputFactory;
use App\Domain\Gateway\Persister\Training\WorkoutSetPersisterGateway;
use App\Domain\Gateway\Provider\Training\SetTypeProviderGateway;
use App\Domain\Gateway\Provider\Training\WorkoutProviderGateway;
use App\Domain\Gateway\Provider\User\UserProviderGateway;
use App\Domain\Validation\Validator\Training\UpdateWorkoutSetValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * Correcting a set, during the workout or after. A set type retired since may stay on it.
 */
final readonly class UpdateWorkoutSetUseCase implements UseCaseInterface
{
    public function __construct(
        private UpdateWorkoutSetValidator $validator,
        private UserProviderGateway $userProviderGateway,
        private WorkoutProviderGateway $workoutProviderGateway,
        private SetTypeProviderGateway $setTypeProviderGateway,
        private WorkoutSetPersisterGateway $workoutSetPersisterGateway,
        private WorkoutOutputFactory $outputFactory,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(int $ownerId, int $workoutId, int $setId, UpdateWorkoutSetDataInput $input): WorkoutDataOutput
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

        $setType = null === $input->setTypeId ? null : $this->setTypeProviderGateway->findOneById($input->setTypeId);
        $this->validator->validate($input, $set, $setType);

        $set->setType = $setType;
        $set->reps = $input->reps;
        $set->weightInKilograms = $input->weightInKilograms;
        $set->durationInSeconds = $input->durationInSeconds;
        $set->distanceInMetres = $input->distanceInMetres;
        $set->rpe = $input->rpe;

        $this->workoutSetPersisterGateway->update($set);

        return $this->outputFactory->buildOne($workout);
    }
}
