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
    public function execute(UserDataModel $currentUser, int $workoutId, AddWorkoutBlockDataInput $input): WorkoutDataOutput
    {
        $workout = $this->workoutProviderGateway->findOneById($workoutId);
        if (null === $workout) {
            throw new DataModelNotFoundException(WorkoutDataModel::class);
        }

        $offered = $this->movementProviderGateway->findAllOfferedByIds($input->movementIds());

        $this->validator->validate($currentUser, $workout, $input, $offered);

        $block = new WorkoutBlockDataModel();
        $block->workout = $workout;
        $block->position = $workout->nextBlockPosition();
        $this->workoutBlockPersisterGateway->create($block);

        $offeredById = [];
        foreach ($offered as $movement) {
            $offeredById[(int) $movement->id] = $movement;
        }

        // Every movement asked for is offered past the validator; the block takes them in the
        // order they were asked for, not the one the database gave them back in.
        foreach ($input->exercises as $position => $requested) {
            $exercise = new WorkoutExerciseDataModel();
            $exercise->block = $block;
            $exercise->movement = $offeredById[$requested->movementId];
            $exercise->position = $position;
            $exercise->restInSeconds = $requested->restInSeconds;
            $this->workoutExercisePersisterGateway->create($exercise);
        }

        return $this->outputFactory->buildOne($workout);
    }
}
