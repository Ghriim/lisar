<?php

declare(strict_types=1);

namespace App\Domain\Factory\OutputFactory\Training;

use App\Domain\DTO\DataModel\Training\MuscleDataModel;
use App\Domain\DTO\DataModel\Training\PersonalBestDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\Output\Training\WorkoutMuscleShareDataOutput;
use App\Domain\DTO\Output\Training\WorkoutStatsDataOutput;

use function array_values;
use function round;
use function usort;

/**
 * Adds a workout up: its sets, the load it moved, the time and the distance it covered, and how
 * its sets fell on the muscles — 1 to a movement's primary muscle, 0.5 to each secondary one.
 */
final readonly class WorkoutStatsOutputFactory
{
    public const float PRIMARY_SHARE = 1.0;
    public const float SECONDARY_SHARE = 0.5;

    public function __construct(private PersonalBestOutputFactory $personalBestOutputFactory)
    {
    }

    /**
     * @param list<PersonalBestDataModel> $personalBests the records that workout beat
     */
    public function buildOne(WorkoutDataModel $workout, array $personalBests = []): WorkoutStatsDataOutput
    {
        $output = new WorkoutStatsDataOutput();
        $output->workoutId = (int) $workout->id;

        /** @var array<int, WorkoutMuscleShareDataOutput> $shares */
        $shares = [];

        foreach ($workout->orderedBlocks() as $block) {
            foreach ($block->orderedExercises() as $exercise) {
                $movement = $exercise->movement;

                foreach ($exercise->sets as $set) {
                    ++$output->setCount;

                    if (null !== $set->reps && null !== $set->weightInKilograms) {
                        $sides = true === $movement->isUnilateral ? 2 : 1;
                        $output->volumeInKilograms = ($output->volumeInKilograms ?? 0.0) + $set->reps * $set->weightInKilograms * $sides;
                    }
                    if (null !== $set->durationInSeconds) {
                        $output->durationInSeconds = ($output->durationInSeconds ?? 0) + $set->durationInSeconds;
                    }
                    if (null !== $set->distanceInMetres) {
                        $output->distanceInMetres = ($output->distanceInMetres ?? 0) + $set->distanceInMetres;
                    }

                    $shares = $this->credit($shares, $movement->primaryMuscle, self::PRIMARY_SHARE);
                    foreach ($movement->secondaryMuscles as $muscle) {
                        $shares = $this->credit($shares, $muscle, self::SECONDARY_SHARE);
                    }
                }
            }
        }

        if (null !== $output->volumeInKilograms) {
            $output->volumeInKilograms = round($output->volumeInKilograms, 2);
        }

        $output->muscles = $this->rank($shares);
        $output->personalBests = $this->personalBestOutputFactory->buildMany($personalBests);

        return $output;
    }

    /**
     * @param array<int, WorkoutMuscleShareDataOutput> $shares
     *
     * @return array<int, WorkoutMuscleShareDataOutput>
     */
    private function credit(array $shares, MuscleDataModel $muscle, float $share): array
    {
        $id = (int) $muscle->id;

        if (false === isset($shares[$id])) {
            $shares[$id] = new WorkoutMuscleShareDataOutput();
            $shares[$id]->muscleId = $id;
            $shares[$id]->muscleName = $muscle->name;
        }
        $shares[$id]->setShare += $share;

        return $shares;
    }

    /**
     * @param array<int, WorkoutMuscleShareDataOutput> $shares
     *
     * @return list<WorkoutMuscleShareDataOutput>
     */
    private function rank(array $shares): array
    {
        $total = 0.0;
        foreach ($shares as $share) {
            $total += $share->setShare;
        }

        $ranked = array_values($shares);
        foreach ($ranked as $share) {
            $share->percentage = round($share->setShare / $total * 100, 1);
        }

        // The most worked first; a tie by name, so the order never depends on the workout's.
        usort($ranked, static fn (WorkoutMuscleShareDataOutput $a, WorkoutMuscleShareDataOutput $b): int => [$b->setShare, $a->muscleName] <=> [$a->setShare, $b->muscleName]);

        return $ranked;
    }
}
