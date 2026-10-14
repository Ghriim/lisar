<?php

declare(strict_types=1);

namespace App\UseCase\Training;

use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Output\Training\WorkoutPreviousPerformanceDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\Training\WorkoutPreviousPerformanceOutputFactory;
use App\Domain\Gateway\Provider\Training\WorkoutProviderGateway;
use App\Domain\Validation\Validator\Training\ListWorkoutPreviousPerformancesValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * What each movement of a workout gave the last time it was done: for each, the sets of the
 * latest finished workout started before this one in which it came. A movement never done before
 * is absent. Read on a past workout, it answers what came before that one.
 */
final readonly class ListWorkoutPreviousPerformancesUseCase implements UseCaseInterface
{
    public function __construct(
        private ListWorkoutPreviousPerformancesValidator $validator,
        private WorkoutProviderGateway $workoutProviderGateway,
        private WorkoutPreviousPerformanceOutputFactory $outputFactory,
    ) {
    }

    /**
     * @return list<WorkoutPreviousPerformanceDataOutput>
     *
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(UserDataModel $currentUser, int $workoutId): array
    {
        $workout = $this->workoutProviderGateway->findOneById($workoutId);
        if (null === $workout) {
            throw new DataModelNotFoundException(WorkoutDataModel::class);
        }

        $this->validator->validate($currentUser, $workout);

        $previousByMovementId = [];
        foreach ($workout->orderedBlocks() as $block) {
            foreach ($block->orderedExercises() as $exercise) {
                $movement = $exercise->movement;
                if (true === isset($previousByMovementId[(int) $movement->id])) {
                    continue;
                }

                $previous = $this->workoutProviderGateway->findLastFinishedWithMovementBefore($currentUser, $movement, $workout->startedAt);
                if (null !== $previous) {
                    $previousByMovementId[(int) $movement->id] = [$movement, $previous];
                }
            }
        }

        return $this->outputFactory->buildMany($previousByMovementId);
    }
}
