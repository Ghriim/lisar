<?php

declare(strict_types=1);

namespace App\UseCase\Workout;

use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\DTO\DataModel\WorkoutDataModel;
use App\Domain\DTO\Input\Workout\ReorderWorkoutBlocksDataInput;
use App\Domain\DTO\Output\Workout\WorkoutDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\WorkoutOutputFactory;
use App\Domain\Gateway\Persister\WorkoutBlockPersisterGateway;
use App\Domain\Gateway\Provider\UserProviderGateway;
use App\Domain\Gateway\Provider\WorkoutProviderGateway;
use App\Domain\Validation\Validator\Workout\ReorderWorkoutBlocksValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * Putting a workout's blocks in a new order. The list names every block, each once.
 */
final readonly class ReorderWorkoutBlocksUseCase implements UseCaseInterface
{
    public function __construct(
        private ReorderWorkoutBlocksValidator $validator,
        private UserProviderGateway $userProviderGateway,
        private WorkoutProviderGateway $workoutProviderGateway,
        private WorkoutBlockPersisterGateway $workoutBlockPersisterGateway,
        private WorkoutOutputFactory $outputFactory,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(int $ownerId, int $workoutId, ReorderWorkoutBlocksDataInput $input): WorkoutDataOutput
    {
        $owner = $this->userProviderGateway->findOneById($ownerId);
        if (null === $owner) {
            throw new DataModelNotFoundException(UserDataModel::class);
        }

        $workout = $this->workoutProviderGateway->findOneByIdForOwner($workoutId, $owner);
        if (null === $workout) {
            throw new DataModelNotFoundException(WorkoutDataModel::class);
        }

        $this->validator->validate($input, $workout);

        foreach ($input->blockIds as $position => $blockId) {
            $block = $workout->findBlock($blockId);
            if (null !== $block && $position !== $block->position) {
                $block->position = $position;
                $this->workoutBlockPersisterGateway->update($block);
            }
        }

        return $this->outputFactory->buildOne($workout);
    }
}
