<?php

declare(strict_types=1);

namespace App\UseCase\Training;

use App\Domain\DTO\DataModel\Training\WorkoutBlockDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutExerciseDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\AddWorkoutExerciseDataInput;
use App\Domain\DTO\Output\Training\WorkoutDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\Training\WorkoutOutputFactory;
use App\Domain\Gateway\Persister\Training\WorkoutExercisePersisterGateway;
use App\Domain\Gateway\Provider\Training\MovementProviderGateway;
use App\Domain\Gateway\Provider\Training\WorkoutProviderGateway;
use App\Domain\Gateway\Provider\User\UserProviderGateway;
use App\Domain\Validation\Validator\Training\AddWorkoutExerciseValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * Adding a movement to a block, after the ones already there — which is how a block becomes a
 * superset. The movement must be offered now.
 */
final readonly class AddWorkoutExerciseUseCase implements UseCaseInterface
{
    public function __construct(
        private AddWorkoutExerciseValidator $validator,
        private UserProviderGateway $userProviderGateway,
        private WorkoutProviderGateway $workoutProviderGateway,
        private MovementProviderGateway $movementProviderGateway,
        private WorkoutExercisePersisterGateway $workoutExercisePersisterGateway,
        private WorkoutOutputFactory $outputFactory,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(int $ownerId, int $workoutId, int $blockId, AddWorkoutExerciseDataInput $input): WorkoutDataOutput
    {
        $owner = $this->userProviderGateway->findOneById($ownerId);
        if (null === $owner) {
            throw new DataModelNotFoundException(UserDataModel::class);
        }

        $workout = $this->workoutProviderGateway->findOneByIdForOwner($workoutId, $owner);
        if (null === $workout) {
            throw new DataModelNotFoundException(WorkoutDataModel::class);
        }

        $block = $workout->findBlock($blockId);
        if (null === $block) {
            throw new DataModelNotFoundException(WorkoutBlockDataModel::class);
        }

        $movement = $this->movementProviderGateway->findOneOfferedById($input->movementId);
        $this->validator->validate($input, $movement);
        if (null === $movement) {
            // Unreachable: the validator refuses a movement that is not offered.
            throw new DataModelNotFoundException(WorkoutExerciseDataModel::class);
        }

        $exercise = new WorkoutExerciseDataModel();
        $exercise->block = $block;
        $exercise->movement = $movement;
        $exercise->position = $block->nextExercisePosition();
        $this->workoutExercisePersisterGateway->create($exercise);
        $block->exercises->add($exercise);

        return $this->outputFactory->buildOne($workout);
    }
}
