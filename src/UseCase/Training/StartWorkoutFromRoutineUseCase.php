<?php

declare(strict_types=1);

namespace App\UseCase\Training;

use App\Domain\DTO\DataModel\Training\RoutineDataModel;
use App\Domain\DTO\DataModel\Training\SetTypeDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Output\Training\WorkoutCopyDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\DataModelFactory\Training\WorkoutDataModelFactory;
use App\Domain\Factory\OutputFactory\Training\WorkoutCopyOutputFactory;
use App\Domain\Gateway\Persister\Training\WorkoutPersisterGateway;
use App\Domain\Gateway\Provider\Training\PersonalBestProviderGateway;
use App\Domain\Gateway\Provider\Training\RoutineProviderGateway;
use App\Domain\Gateway\Provider\Training\SetTypeProviderGateway;
use App\Domain\Gateway\Provider\Training\WorkoutProviderGateway;
use App\Domain\Tracking\DayClock;
use App\Domain\Training\RoutineTree;
use App\Domain\Validation\Validator\Training\StartWorkoutFromRoutineValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * Starting a workout now, laid out like one of the caller's routines, each load worked out from
 * their estimated 1RMs of the day. Refused while a workout is in progress, and for a routine that
 * would give no set.
 */
final readonly class StartWorkoutFromRoutineUseCase implements UseCaseInterface
{
    public function __construct(
        private StartWorkoutFromRoutineValidator $validator,
        private RoutineProviderGateway $routineProviderGateway,
        private WorkoutProviderGateway $workoutProviderGateway,
        private SetTypeProviderGateway $setTypeProviderGateway,
        private PersonalBestProviderGateway $personalBestProviderGateway,
        private WorkoutPersisterGateway $workoutPersisterGateway,
        private WorkoutDataModelFactory $workoutDataModelFactory,
        private WorkoutCopyOutputFactory $outputFactory,
        private RoutineTree $routineTree,
        private DayClock $clock,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(UserDataModel $currentUser, int $routineId): WorkoutCopyDataOutput
    {
        $routine = $this->routineProviderGateway->findOneById($routineId);
        if (null === $routine) {
            throw new DataModelNotFoundException(RoutineDataModel::class);
        }

        $defaultSetType = $this->setTypeProviderGateway->findOneDefault();
        if (null === $defaultSetType) {
            throw new DataModelNotFoundException(SetTypeDataModel::class);
        }

        // Built before it is validated: whether it gives a set is known only once what is no
        // longer offered has been left out. Nothing is written until the validator has run.
        $now = $this->clock->now();
        $workout = $this->workoutDataModelFactory->buildFromRoutine(
            $routine,
            $currentUser,
            $now,
            $this->clock->inDisplayZone($now),
            $defaultSetType,
            $this->personalBestProviderGateway->findEstimatedOneRepMaxesForOwner($currentUser, $this->routineTree->movements($routine)),
        );

        $this->validator->validate($currentUser, $routine, $this->workoutProviderGateway->findOneInProgressForOwner($currentUser), $workout->countSets());

        $this->workoutPersisterGateway->create($workout);

        return $this->outputFactory->buildOne($workout, $this->workoutDataModelFactory->movementsLeftOutOfRoutine($routine));
    }
}
