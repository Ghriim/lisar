<?php

declare(strict_types=1);

namespace App\UseCase\Training;

use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Output\Training\WorkoutCopyDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\DataModelFactory\Training\WorkoutDataModelFactory;
use App\Domain\Factory\OutputFactory\Training\WorkoutCopyOutputFactory;
use App\Domain\Gateway\Persister\Training\WorkoutPersisterGateway;
use App\Domain\Gateway\Provider\Training\WorkoutProviderGateway;
use App\Domain\Gateway\Provider\User\UserProviderGateway;
use App\Domain\Tracking\DayClock;
use App\Domain\Validation\Constraint\Training\WorkoutNotInProgressConstraint;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * Starting a workout now, laid out like a past one: its blocks, movements and sets, the sets
 * still to do. Refused while a workout is in progress — which also covers copying that very one:
 * only a finished workout can be done again.
 */
final readonly class CopyWorkoutUseCase implements UseCaseInterface
{
    public const string ERROR_CODE = 'copy_workout_invalid';

    public function __construct(
        private UserProviderGateway $userProviderGateway,
        private WorkoutProviderGateway $workoutProviderGateway,
        private WorkoutPersisterGateway $workoutPersisterGateway,
        private WorkoutDataModelFactory $workoutDataModelFactory,
        private WorkoutCopyOutputFactory $outputFactory,
        private DayClock $clock,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(int $ownerId, int $workoutId): WorkoutCopyDataOutput
    {
        $owner = $this->userProviderGateway->findOneById($ownerId);
        if (null === $owner) {
            throw new DataModelNotFoundException(UserDataModel::class);
        }

        $source = $this->workoutProviderGateway->findOneByIdForOwner($workoutId, $owner);
        if (null === $source) {
            throw new DataModelNotFoundException(WorkoutDataModel::class);
        }

        $violations = WorkoutNotInProgressConstraint::validate($this->workoutProviderGateway->findOneInProgressForOwner($owner));
        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }

        $copy = $this->workoutDataModelFactory->buildCopy($source, $this->clock->now());
        $this->workoutPersisterGateway->createWhole($copy);

        return $this->outputFactory->buildOne($copy, $this->workoutDataModelFactory->movementsLeftOut($source));
    }
}
