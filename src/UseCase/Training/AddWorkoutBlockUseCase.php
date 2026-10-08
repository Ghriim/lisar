<?php

declare(strict_types=1);

namespace App\UseCase\Training;

use App\Domain\DTO\DataModel\Training\WorkoutBlockDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutExerciseDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\AddWorkoutBlockDataInput;
use App\Domain\DTO\Output\Training\WorkoutDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\Training\WorkoutOutputFactory;
use App\Domain\Gateway\Persister\Training\WorkoutBlockPersisterGateway;
use App\Domain\Gateway\Persister\Training\WorkoutExercisePersisterGateway;
use App\Domain\Gateway\Provider\Training\MovementProviderGateway;
use App\Domain\Gateway\Provider\Training\WorkoutProviderGateway;
use App\Domain\Gateway\Provider\User\UserProviderGateway;
use App\Domain\Validation\Validator\Training\AddWorkoutBlockValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * Adding a block at the end of a workout: one movement, or several back to back — a superset, in
 * the order given, each with its own rest. Each must be offered now.
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
        foreach ($input->movementIds() as $movementId) {
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

        // Every movement asked for is offered past the validator, so the two lists line up.
        foreach ($offered as $position => $movement) {
            $exercise = new WorkoutExerciseDataModel();
            $exercise->block = $block;
            $exercise->movement = $movement;
            $exercise->position = $position;
            $exercise->restInSeconds = $input->exercises[$position]->restInSeconds;
            $this->workoutExercisePersisterGateway->create($exercise);
            $block->exercises->add($exercise);
        }

        return $this->outputFactory->buildOne($workout);
    }
}
