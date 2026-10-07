<?php

declare(strict_types=1);

namespace App\Domain\Factory\DataModelFactory\Training;

use App\Domain\DTO\DataModel\Training\MovementDataModel;
use App\Domain\DTO\DataModel\Training\SetTypeDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutBlockDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutExerciseDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutSetDataModel;
use App\Domain\Validation\Constraint\Training\WorkoutSetMeasuresConstraint;
use DateTimeImmutable;

use function in_array;

final readonly class WorkoutDataModelFactory
{
    /**
     * A new workout in progress, laid out like `$source`: its name, its blocks and movements in
     * order, each movement's note, and every set as one still to do — same measures, RPE and type,
     * not ticked. The workout's own note and feeling stay behind: they tell what happened that day.
     *
     * Only what is offered now is taken over: a movement retired since is left out, and a block
     * left empty with it. A set type retired since gives way to `$defaultSetType`. A set keeps only
     * the measures its movement tracks now, and is left out when one it now tracks is missing.
     */
    public function buildCopy(WorkoutDataModel $source, DateTimeImmutable $startedAt, SetTypeDataModel $defaultSetType): WorkoutDataModel
    {
        $workout = new WorkoutDataModel();
        $workout->owner = $source->owner;
        $workout->name = $source->name;
        $workout->startedAt = $startedAt;

        foreach ($source->orderedBlocks() as $sourceBlock) {
            $block = new WorkoutBlockDataModel();
            $block->workout = $workout;
            $block->position = $workout->nextBlockPosition();

            foreach ($sourceBlock->orderedExercises() as $sourceExercise) {
                if (false === $sourceExercise->movement->isOffered()) {
                    continue;
                }

                $block->exercises->add($this->copyExercise($sourceExercise, $block, $defaultSetType));
            }

            if (false === $block->exercises->isEmpty()) {
                $workout->blocks->add($block);
            }
        }

        return $workout;
    }

    /**
     * The movements of `$source` that a copy leaves out, each once, in the order they come.
     *
     * @return list<MovementDataModel>
     */
    public function movementsLeftOut(WorkoutDataModel $source): array
    {
        $leftOut = [];
        foreach ($source->orderedBlocks() as $block) {
            foreach ($block->orderedExercises() as $exercise) {
                if (false === $exercise->movement->isOffered() && false === in_array($exercise->movement, $leftOut, true)) {
                    $leftOut[] = $exercise->movement;
                }
            }
        }

        return $leftOut;
    }

    private function copyExercise(WorkoutExerciseDataModel $source, WorkoutBlockDataModel $block, SetTypeDataModel $defaultSetType): WorkoutExerciseDataModel
    {
        $exercise = new WorkoutExerciseDataModel();
        $exercise->block = $block;
        $exercise->movement = $source->movement;
        $exercise->position = $block->nextExercisePosition();
        $exercise->note = $source->note;

        foreach ($source->orderedSets() as $sourceSet) {
            $set = $this->copySet($sourceSet, $exercise, $defaultSetType);
            if (null !== $set) {
                $exercise->sets->add($set);
            }
        }

        return $exercise;
    }

    private function copySet(WorkoutSetDataModel $source, WorkoutExerciseDataModel $exercise, SetTypeDataModel $defaultSetType): ?WorkoutSetDataModel
    {
        $movement = $exercise->movement;

        $set = new WorkoutSetDataModel();
        $set->exercise = $exercise;
        $set->position = $exercise->nextSetPosition();
        $set->reps = true === $movement->tracksReps ? $source->reps : null;
        $set->weightInKilograms = true === $movement->tracksWeight ? $source->weightInKilograms : null;
        $set->durationInSeconds = true === $movement->tracksDuration ? $source->durationInSeconds : null;
        $set->distanceInMetres = true === $movement->tracksDistance ? $source->distanceInMetres : null;
        $set->rpe = $source->rpe;
        $set->setType = true === $source->setType->isActive ? $source->setType : $defaultSetType;
        $set->isComplete = false;

        $violations = WorkoutSetMeasuresConstraint::validate($movement, $set->reps, $set->weightInKilograms, $set->durationInSeconds, $set->distanceInMetres);

        return [] === $violations ? $set : null;
    }
}
