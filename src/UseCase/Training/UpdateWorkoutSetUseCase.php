<?php

declare(strict_types=1);

namespace App\UseCase\Training;

use App\Domain\DTO\DataModel\Training\SetTypeDataModel;
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
use App\Domain\Gateway\Provider\Training\WorkoutSetProviderGateway;
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
        private WorkoutProviderGateway $workoutProviderGateway,
        private WorkoutSetProviderGateway $workoutSetProviderGateway,
        private SetTypeProviderGateway $setTypeProviderGateway,
        private WorkoutSetPersisterGateway $workoutSetPersisterGateway,
        private WorkoutOutputFactory $outputFactory,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(UserDataModel $currentUser, int $workoutId, int $setId, UpdateWorkoutSetDataInput $input): WorkoutDataOutput
    {
        $workout = $this->workoutProviderGateway->findOneById($workoutId);
        if (null === $workout) {
            throw new DataModelNotFoundException(WorkoutDataModel::class);
        }

        $set = $this->workoutSetProviderGateway->findOneById($setId);
        if (null === $set) {
            throw new DataModelNotFoundException(WorkoutSetDataModel::class);
        }

        $setType = null === $input->setTypeId ? null : $this->setTypeProviderGateway->findOneById($input->setTypeId);
        $this->validator->validate($currentUser, $workout, $set, $input, $setType);

        // No type named: the default one, an ordinary working set.
        $set->setType = $setType ?? $this->findDefaultSetType();
        $set->reps = $input->reps;
        $set->weightInKilograms = null === $input->weightInKilograms ? null : round($input->weightInKilograms, UpdateWorkoutSetDataInput::DECIMALS);
        $set->durationInSeconds = $input->durationInSeconds;
        $set->distanceInMetres = $input->distanceInMetres;
        $set->rpe = $input->rpe;

        $this->workoutSetPersisterGateway->update($set);

        return $this->outputFactory->buildOne($workout);
    }

    /**
     * @throws DataModelNotFoundException
     */
    private function findDefaultSetType(): SetTypeDataModel
    {
        $default = $this->setTypeProviderGateway->findOneDefault();
        if (null === $default) {
            throw new DataModelNotFoundException(SetTypeDataModel::class);
        }

        return $default;
    }
}
