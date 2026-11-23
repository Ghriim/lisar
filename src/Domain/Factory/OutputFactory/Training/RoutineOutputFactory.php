<?php

declare(strict_types=1);

namespace App\Domain\Factory\OutputFactory\Training;

use App\Domain\DTO\DataModel\Training\RoutineBlockDataModel;
use App\Domain\DTO\DataModel\Training\RoutineDataModel;
use App\Domain\DTO\DataModel\Training\RoutineExerciseDataModel;
use App\Domain\DTO\DataModel\Training\RoutineSetDataModel;
use App\Domain\DTO\Output\Training\RoutineBlockDataOutput;
use App\Domain\DTO\Output\Training\RoutineDataOutput;
use App\Domain\DTO\Output\Training\RoutineExerciseDataOutput;
use App\Domain\DTO\Output\Training\RoutineSetDataOutput;
use App\Domain\DTO\Output\Training\WorkoutMovementDataOutput;
use App\Domain\Training\PrescribedLoad;
use App\Domain\Training\RoutineTree;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final readonly class RoutineOutputFactory
{
    public function __construct(
        private ObjectMapperInterface $mapper,
        private RoutineTree $routineTree,
        private PrescribedLoad $prescribedLoad,
    ) {
    }

    /**
     * @param array<int, float> $estimatedOneRepMaxes the caller's, by movement id: what each set's
     *                                                load today is worked out from
     */
    public function buildOne(RoutineDataModel $routine, array $estimatedOneRepMaxes): RoutineDataOutput
    {
        $output = $this->mapper->map($routine, RoutineDataOutput::class);

        foreach ($this->routineTree->orderedBlocks($routine) as $block) {
            $output->blocks[] = $this->buildBlock($block, $estimatedOneRepMaxes);
        }

        return $output;
    }

    /** @param array<int, float> $estimatedOneRepMaxes */
    private function buildBlock(RoutineBlockDataModel $block, array $estimatedOneRepMaxes): RoutineBlockDataOutput
    {
        $output = new RoutineBlockDataOutput();
        $output->id = (int) $block->id;

        foreach ($this->routineTree->orderedExercises($block) as $exercise) {
            $output->exercises[] = $this->buildExercise($exercise, $estimatedOneRepMaxes[(int) $exercise->movement->id] ?? null);
        }

        return $output;
    }

    private function buildExercise(RoutineExerciseDataModel $exercise, ?float $estimatedOneRepMax): RoutineExerciseDataOutput
    {
        $output = new RoutineExerciseDataOutput();
        $output->id = (int) $exercise->id;
        $output->movement = $this->mapper->map($exercise->movement, WorkoutMovementDataOutput::class);
        $output->note = $exercise->note;
        $output->restInSeconds = $exercise->restInSeconds;

        foreach ($this->routineTree->orderedSets($exercise) as $set) {
            $output->sets[] = $this->buildSet($exercise, $set, $estimatedOneRepMax);
        }

        return $output;
    }

    private function buildSet(RoutineExerciseDataModel $exercise, RoutineSetDataModel $set, ?float $estimatedOneRepMax): RoutineSetDataOutput
    {
        $output = new RoutineSetDataOutput();
        $output->id = (int) $set->id;
        $output->reps = $set->reps;
        $output->percentageOfOneRepMax = $set->percentageOfOneRepMax;
        $output->estimatedLoadInKilograms = $this->prescribedLoad->of($exercise->movement, $set->percentageOfOneRepMax, $estimatedOneRepMax);

        return $output;
    }
}
