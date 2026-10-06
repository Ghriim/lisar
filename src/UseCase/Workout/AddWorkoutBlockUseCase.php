<?php

declare(strict_types=1);

namespace App\UseCase\Workout;

use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\DTO\DataModel\WorkoutBlockDataModel;
use App\Domain\DTO\DataModel\WorkoutDataModel;
use App\Domain\DTO\DataModel\WorkoutExerciseDataModel;
use App\Domain\DTO\Input\Workout\AddWorkoutBlockDataInput;
use App\Domain\DTO\Output\Workout\WorkoutDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\WorkoutOutputFactory;
use App\Domain\Gateway\Persister\WorkoutBlockPersisterGateway;
use App\Domain\Gateway\Persister\WorkoutExercisePersisterGateway;
use App\Domain\Gateway\Provider\MovementProviderGateway;
use App\Domain\Gateway\Provider\UserProviderGateway;
use App\Domain\Gateway\Provider\WorkoutProviderGateway;
use App\Domain\Validation\Validator\Workout\AddWorkoutBlockValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * Adding a block at the end of a workout: one movement, or several back to back — a superset, in
 * the order given. Each must be offered now.
 */
final readonly class AddWorkoutBlockUseCase implements UseCaseInterface
{
    public function __construct(
        private AddWorkoutBlockValidator $validator,
        private UserProviderGateway $userProviderGateway,
        private WorkoutProviderGateway $workoutProviderGateway,
        private MovementProviderGateway $movementProviderGateway,
        private WorkoutBlockPersisterGateway $workoutBlockPersisterGateway,
        private WorkoutExercisePersisterGateway $workoutExercisePersisterGateway,
        private WorkoutOutputFactory $outputFactory,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(int $ownerId, int $workoutId, AddWorkoutBlockDataInput $input): WorkoutDataOutput
    {
        $owner = $this->userProviderGateway->findOneById($ownerId);
        if (null === $owner) {
            throw new DataModelNotFoundException(UserDataModel::class);
        }

        $workout = $this->workoutProviderGateway->findOneByIdForOwner($workoutId, $owner);
        if (null === $workout) {
            throw new DataModelNotFoundException(WorkoutDataModel::class);
        }

        $offered = [];
        foreach ($input->movementIds as $movementId) {
            $movement = $this->movementProviderGateway->findOneOfferedById($movementId);
            if (null !== $movement) {
                $offered[] = $movement;
            }
        }

        $this->validator->validate($input, $offered);

        $block = new WorkoutBlockDataModel();
        $block->workout = $workout;
        $block->position = $workout->nextBlockPosition();
        $this->workoutBlockPersisterGateway->create($block);
        $workout->blocks->add($block);

        foreach ($offered as $position => $movement) {
            $exercise = new WorkoutExerciseDataModel();
            $exercise->block = $block;
            $exercise->movement = $movement;
            $exercise->position = $position;
            $this->workoutExercisePersisterGateway->create($exercise);
            $block->exercises->add($exercise);
        }

        return $this->outputFactory->buildOne($workout);
    }
}
