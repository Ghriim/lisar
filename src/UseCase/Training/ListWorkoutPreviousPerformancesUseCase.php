<?php

declare(strict_types=1);

namespace App\UseCase\Training;

use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Output\Training\WorkoutPreviousPerformanceDataOutput;
use App\Domain\Factory\OutputFactory\Training\WorkoutPreviousPerformanceOutputFactory;
use App\Domain\Gateway\Provider\Training\WorkoutProviderGateway;
use App\Domain\Gateway\Provider\User\UserProviderGateway;
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
        private UserProviderGateway $userProviderGateway,
        private WorkoutProviderGateway $workoutProviderGateway,
        private WorkoutPreviousPerformanceOutputFactory $outputFactory,
    ) {
    }

    /**
     * @return list<WorkoutPreviousPerformanceDataOutput>
     *
     * @throws DataModelNotFoundException
     */
    public function execute(int $ownerId, int $workoutId): array
    {
        $owner = $this->userProviderGateway->findOneById($ownerId);
        if (null === $owner) {
            throw new DataModelNotFoundException(UserDataModel::class);
        }

        $workout = $this->workoutProviderGateway->findOneByIdForOwner($workoutId, $owner);
        if (null === $workout) {
            throw new DataModelNotFoundException(WorkoutDataModel::class);
        }

        $previousByMovementId = [];
        foreach ($workout->orderedBlocks() as $block) {
            foreach ($block->orderedExercises() as $exercise) {
                $movement = $exercise->movement;
                if (true === isset($previousByMovementId[(int) $movement->id])) {
                    continue;
                }

                $previous = $this->workoutProviderGateway->findLastFinishedWithMovementBefore($owner, $movement, $workout->startedAt);
                if (null !== $previous) {
                    $previousByMovementId[(int) $movement->id] = [$movement, $previous];
                }
            }
        }

        return $this->outputFactory->buildMany($previousByMovementId);
    }
}
