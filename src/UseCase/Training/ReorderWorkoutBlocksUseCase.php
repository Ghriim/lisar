<?php

declare(strict_types=1);

namespace App\UseCase\Training;

use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\ReorderWorkoutBlocksDataInput;
use App\Domain\DTO\Output\Training\WorkoutDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\Training\WorkoutOutputFactory;
use App\Domain\Gateway\Persister\Training\WorkoutBlockPersisterGateway;
use App\Domain\Gateway\Provider\Training\WorkoutProviderGateway;
use App\Domain\Validation\Validator\Training\ReorderWorkoutBlocksValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * Putting a workout's blocks in a new order. The list names every block, each once.
 */
final readonly class ReorderWorkoutBlocksUseCase implements UseCaseInterface
{
    public function __construct(
        private ReorderWorkoutBlocksValidator $validator,
        private WorkoutProviderGateway $workoutProviderGateway,
        private WorkoutBlockPersisterGateway $workoutBlockPersisterGateway,
        private WorkoutOutputFactory $outputFactory,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(UserDataModel $currentUser, int $workoutId, ReorderWorkoutBlocksDataInput $input): WorkoutDataOutput
    {
        $workout = $this->workoutProviderGateway->findOneById($workoutId);
        if (null === $workout) {
            throw new DataModelNotFoundException(WorkoutDataModel::class);
        }

        $this->validator->validate($currentUser, $input, $workout);

        // The validator saw to it that the list names every block of the workout, each once.
        $positions = array_flip($input->blockIds);
        foreach ($workout->blocks as $block) {
            $position = $positions[$block->id] ?? $block->position;
            if ($position !== $block->position) {
                $block->position = $position;
                $this->workoutBlockPersisterGateway->update($block);
            }
        }

        return $this->outputFactory->buildOne($workout);
    }
}
