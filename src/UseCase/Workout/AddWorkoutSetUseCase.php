<?php

declare(strict_types=1);

namespace App\UseCase\Workout;

use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\DTO\DataModel\WorkoutDataModel;
use App\Domain\DTO\DataModel\WorkoutExerciseDataModel;
use App\Domain\DTO\DataModel\WorkoutSetDataModel;
use App\Domain\DTO\Input\Workout\AddWorkoutSetDataInput;
use App\Domain\DTO\Output\Workout\WorkoutDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\WorkoutOutputFactory;
use App\Domain\Gateway\Persister\WorkoutSetPersisterGateway;
use App\Domain\Gateway\Provider\SetTypeProviderGateway;
use App\Domain\Gateway\Provider\UserProviderGateway;
use App\Domain\Gateway\Provider\WorkoutProviderGateway;
use App\Domain\Validation\Validator\Workout\AddWorkoutSetValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * Logging a set on a movement of a workout, after the ones already there — in progress or
 * finished: a forgotten set can be added afterwards.
 *
 * During the workout a set is logged before it is done, and ticked once it is. Added to a
 * finished workout, it was done already: it comes ticked.
 */
final readonly class AddWorkoutSetUseCase implements UseCaseInterface
{
    public function __construct(
        private AddWorkoutSetValidator $validator,
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
    public function execute(int $ownerId, int $workoutId, int $exerciseId, AddWorkoutSetDataInput $input): WorkoutDataOutput
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

        $setType = null === $input->setTypeId ? null : $this->setTypeProviderGateway->findOneById($input->setTypeId);
        $this->validator->validate($input, $exercise->movement, $setType);

        $set = new WorkoutSetDataModel();
        $set->exercise = $exercise;
        $set->position = $exercise->nextSetPosition();
        $set->setType = $setType;
        $set->reps = $input->reps;
        $set->weightInKilograms = $input->weightInKilograms;
        $set->durationInSeconds = $input->durationInSeconds;
        $set->distanceInMetres = $input->distanceInMetres;
        $set->rpe = $input->rpe;
        $set->isComplete = false === $workout->isInProgress();

        $this->workoutSetPersisterGateway->create($set);
        $exercise->sets->add($set);

        return $this->outputFactory->buildOne($workout);
    }
}
