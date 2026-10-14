<?php

declare(strict_types=1);

namespace App\UseCase\Training;

use App\Domain\DTO\DataModel\Training\WorkoutBlockDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Output\Training\WorkoutDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\Training\WorkoutOutputFactory;
use App\Domain\Gateway\Persister\Training\WorkoutBlockPersisterGateway;
use App\Domain\Gateway\Provider\Training\WorkoutBlockProviderGateway;
use App\Domain\Gateway\Provider\Training\WorkoutProviderGateway;
use App\Domain\Validation\Validator\Training\DeleteWorkoutBlockValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * Removing a block from a workout — the movement, or every movement of the superset, with their
 * sets.
 */
final readonly class DeleteWorkoutBlockUseCase implements UseCaseInterface
{
    public function __construct(
        private DeleteWorkoutBlockValidator $validator,
        private WorkoutProviderGateway $workoutProviderGateway,
        private WorkoutBlockProviderGateway $workoutBlockProviderGateway,
        private WorkoutBlockPersisterGateway $workoutBlockPersisterGateway,
        private WorkoutOutputFactory $outputFactory,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(UserDataModel $currentUser, int $workoutId, int $blockId): WorkoutDataOutput
    {
        $workout = $this->workoutProviderGateway->findOneById($workoutId);
        if (null === $workout) {
            throw new DataModelNotFoundException(WorkoutDataModel::class);
        }

        $block = $this->workoutBlockProviderGateway->findOneById($blockId);
        if (null === $block) {
            throw new DataModelNotFoundException(WorkoutBlockDataModel::class);
        }

        $this->validator->validate($currentUser, $workout, $block);

        $this->workoutBlockPersisterGateway->delete($block);

        return $this->outputFactory->buildOne($workout);
    }
}
