<?php

declare(strict_types=1);

namespace App\Domain\Factory\OutputFactory\Training;

use App\Domain\DataTransformer\DateDataTransformer;
use App\Domain\DTO\DataModel\Training\MovementDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\Output\Training\WorkoutPreviousPerformanceDataOutput;

final readonly class WorkoutPreviousPerformanceOutputFactory
{
    public function __construct(private WorkoutSetOutputFactory $workoutSetOutputFactory)
    {
    }

    /**
     * @param array<int, array{MovementDataModel, WorkoutDataModel}> $previousByMovementId each movement with the workout it was last done in
     *
     * @return list<WorkoutPreviousPerformanceDataOutput>
     */
    public function buildMany(array $previousByMovementId): array
    {
        $outputs = [];
        foreach ($previousByMovementId as [$movement, $workout]) {
            $outputs[] = $this->buildOne($movement, $workout);
        }

        return $outputs;
    }

    /** The movement's sets in that workout, every time it came, in workout order. */
    public function buildOne(MovementDataModel $movement, WorkoutDataModel $workout): WorkoutPreviousPerformanceDataOutput
    {
        $output = new WorkoutPreviousPerformanceDataOutput();
        $output->movementId = (int) $movement->id;
        $output->workoutId = (int) $workout->id;
        $output->startedAt = (string) DateDataTransformer::dateToString($workout->startedAt);

        foreach ($workout->orderedBlocks() as $block) {
            foreach ($block->orderedExercises() as $exercise) {
                if ($movement->id === $exercise->movement->id) {
                    foreach ($this->workoutSetOutputFactory->buildMany($exercise->orderedSets()) as $set) {
                        $output->sets[] = $set;
                    }
                }
            }
        }

        return $output;
    }
}
