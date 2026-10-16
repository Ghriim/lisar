<?php

declare(strict_types=1);

namespace App\UseCase\Training;

use App\Domain\DTO\DataModel\Training\SetTypeDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Output\Training\WorkoutCopyDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\DataModelFactory\Training\WorkoutDataModelFactory;
use App\Domain\Factory\OutputFactory\Training\WorkoutCopyOutputFactory;
use App\Domain\Gateway\Persister\Training\WorkoutPersisterGateway;
use App\Domain\Gateway\Provider\Training\SetTypeProviderGateway;
use App\Domain\Gateway\Provider\Training\WorkoutProviderGateway;
use App\Domain\Tracking\DayClock;
use App\Domain\Validation\Validator\Training\CopyWorkoutValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * Starting a workout now, laid out like a past one: its blocks, movements and sets, the sets
 * still to do. Refused while a workout is in progress — which also covers copying that very one:
 * only a finished workout can be done again.
 */
final readonly class CopyWorkoutUseCase implements UseCaseInterface
{
    public function __construct(
        private CopyWorkoutValidator $validator,
        private WorkoutProviderGateway $workoutProviderGateway,
        private SetTypeProviderGateway $setTypeProviderGateway,
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
    public function execute(UserDataModel $currentUser, int $workoutId): WorkoutCopyDataOutput
    {
        $source = $this->workoutProviderGateway->findOneById($workoutId);
        if (null === $source) {
            throw new DataModelNotFoundException(WorkoutDataModel::class);
        }

        $this->validator->validate($currentUser, $source, $this->workoutProviderGateway->findOneInProgressForOwner($currentUser));

        // A set type retired since gives way to the default one.
        $defaultSetType = $this->setTypeProviderGateway->findOneDefault();
        if (null === $defaultSetType) {
            throw new DataModelNotFoundException(SetTypeDataModel::class);
        }

        $copy = $this->workoutDataModelFactory->buildCopy($source, $this->clock->now(), $defaultSetType);
        $this->workoutPersisterGateway->create($copy);

        return $this->outputFactory->buildOne($copy, $this->workoutDataModelFactory->movementsLeftOut($source));
    }
}
