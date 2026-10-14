<?php

declare(strict_types=1);

namespace App\UseCase\Training;

use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\StartWorkoutDataInput;
use App\Domain\DTO\Output\Training\WorkoutDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\Training\WorkoutOutputFactory;
use App\Domain\Gateway\Persister\Training\WorkoutPersisterGateway;
use App\Domain\Gateway\Provider\Training\WorkoutProviderGateway;
use App\Domain\Tracking\DayClock;
use App\Domain\Validation\Validator\Training\StartWorkoutValidator;
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
    public function execute(UserDataModel $currentUser, StartWorkoutDataInput $input): WorkoutDataOutput
    {
        $this->validator->validate($currentUser, $input, $this->workoutProviderGateway->findOneInProgressForOwner($currentUser));

        $workout = new WorkoutDataModel();
        $workout->owner = $currentUser;
        $workout->name = $input->getName();
        $workout->startedAt = $this->clock->now();

        $this->workoutPersisterGateway->create($workout);

        return $this->outputFactory->buildOne($workout);
    }
}
