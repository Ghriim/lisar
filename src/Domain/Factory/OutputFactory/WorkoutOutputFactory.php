<?php

declare(strict_types=1);

namespace App\Domain\Factory\OutputFactory;

use App\Domain\DTO\DataModel\MovementDataModel;
use App\Domain\DTO\DataModel\WorkoutBlockDataModel;
use App\Domain\DTO\DataModel\WorkoutDataModel;
use App\Domain\DTO\DataModel\WorkoutExerciseDataModel;
use App\Domain\DTO\Output\Workout\WorkoutBlockDataOutput;
use App\Domain\DTO\Output\Workout\WorkoutDataOutput;
use App\Domain\DTO\Output\Workout\WorkoutExerciseDataOutput;
use App\Domain\DTO\Output\Workout\WorkoutMovementDataOutput;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

/**
 * A workout, whole: blocks, exercises and sets each by position, then by id.
 */
final readonly class WorkoutOutputFactory
{
    public function __construct(
        private ObjectMapperInterface $mapper,
        private WorkoutSetOutputFactory $workoutSetOutputFactory,
    ) {
    }

    public function buildOne(WorkoutDataModel $workout): WorkoutDataOutput
    {
        $output = $this->mapper->map($workout, WorkoutDataOutput::class);
        $output->isInProgress = $workout->isInProgress();

        foreach ($workout->orderedBlocks() as $block) {
            $output->blocks[] = $this->buildBlock($block);
        }

        return $output;
    }

    private function buildBlock(WorkoutBlockDataModel $block): WorkoutBlockDataOutput
    {
        $output = new WorkoutBlockDataOutput();
        $output->id = (int) $block->id;

        foreach ($block->orderedExercises() as $exercise) {
            $output->exercises[] = $this->buildExercise($exercise);
        }

        return $output;
    }

    private function buildExercise(WorkoutExerciseDataModel $exercise): WorkoutExerciseDataOutput
    {
        $output = new WorkoutExerciseDataOutput();
        $output->id = (int) $exercise->id;
        $output->movement = $this->buildMovement($exercise->movement);
        $output->note = $exercise->note;
        $output->sets = $this->workoutSetOutputFactory->buildMany($exercise->orderedSets());

        return $output;
    }

    private function buildMovement(MovementDataModel $movement): WorkoutMovementDataOutput
    {
        return $this->mapper->map($movement, WorkoutMovementDataOutput::class);
    }
}
