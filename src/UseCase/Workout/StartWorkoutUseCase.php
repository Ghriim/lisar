<?php

declare(strict_types=1);

namespace App\UseCase\Workout;

use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\DTO\DataModel\WorkoutDataModel;
use App\Domain\DTO\Input\Workout\StartWorkoutDataInput;
use App\Domain\DTO\Output\Workout\WorkoutDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\WorkoutOutputFactory;
use App\Domain\Gateway\Persister\WorkoutPersisterGateway;
use App\Domain\Gateway\Provider\UserProviderGateway;
use App\Domain\Gateway\Provider\WorkoutProviderGateway;
use App\Domain\Tracking\DayClock;
use App\Domain\Validation\Validator\Workout\StartWorkoutValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * Starting a workout, now and empty. Refused while another one is in progress: that one is
 * finished or abandoned first.
 */
final readonly class StartWorkoutUseCase implements UseCaseInterface
{
    public function __construct(
        private StartWorkoutValidator $validator,
        private UserProviderGateway $userProviderGateway,
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
    public function execute(int $ownerId, StartWorkoutDataInput $input): WorkoutDataOutput
    {
        $owner = $this->userProviderGateway->findOneById($ownerId);
        if (null === $owner) {
            throw new DataModelNotFoundException(UserDataModel::class);
        }

        $this->validator->validate($input, $this->workoutProviderGateway->findOneInProgressForOwner($owner));

        $workout = new WorkoutDataModel();
        $workout->owner = $owner;
        $workout->name = $input->getName();
        $workout->startedAt = $this->clock->now();

        $this->workoutPersisterGateway->create($workout);

        return $this->outputFactory->buildOne($workout);
    }
}
