<?php

declare(strict_types=1);

namespace App\Domain\Factory\DataModelFactory\Training;

use App\Domain\DTO\DataModel\Training\MovementDataModel;
use App\Domain\DTO\DataModel\Training\RoutineDataModel;
use App\Domain\DTO\DataModel\Training\RoutineExerciseDataModel;
use App\Domain\DTO\DataModel\Training\RoutineSetDataModel;
use App\Domain\DTO\DataModel\Training\SetTypeDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutBlockDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutExerciseDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutSetDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Training\PrescribedLoad;
use App\Domain\Training\RoutineTree;
use App\Domain\Validation\Constraint\Training\WorkoutSetMeasuresConstraint;
use DateTimeImmutable;

use function in_array;

final readonly class WorkoutDataModelFactory
{
    public function __construct(
        private RoutineTree $routineTree,
        private PrescribedLoad $prescribedLoad,
    ) {
    }

    /**
     * A new workout in progress, laid out like `$source`: its name, its blocks and movements in
     * order, each movement's note and rest, and every set as one still to do — same measures, RPE
     * and type, not ticked. The workout's own note and feeling stay behind: they tell what happened that day.
     *
     * Only what is offered now is taken over: a movement retired since is left out, and a block
     * left empty with it. A set type retired since gives way to `$defaultSetType`. A set keeps only
     * the measures its movement tracks now, and is left out when one it now tracks is missing —
     * but the load, which a set still to do may wait for.
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

    /**
     * A new workout in progress of `$owner`'s, laid out like the routine and linked to it, named
     * after it and the day: « Push · 24/10/2026 ». Its blocks and movements in order, each
     * movement's note and rest, and every set as one still to do, of `$defaultSetType`: its reps,
     * and its load worked out from the movement's estimated 1RM — none when there is no 1RM yet.
     *
     * As for a copy, only what is offered now is taken over: a movement retired since is left
     * out, and a block left empty with it. A set its movement could no longer log — it stopped
     * tracking reps since — is left out too.
     *
     * @param DateTimeImmutable $day         the day it starts on, as a person reads it
     * @param array<int, float> $oneRepMaxes `$owner`'s estimated 1RM of each movement, by movement id
     */
    public function buildFromRoutine(
        RoutineDataModel $routine,
        UserDataModel $owner,
        DateTimeImmutable $startedAt,
        DateTimeImmutable $day,
        SetTypeDataModel $defaultSetType,
        array $oneRepMaxes,
    ): WorkoutDataModel {
        $workout = new WorkoutDataModel();
        $workout->owner = $owner;
        $workout->routine = $routine;
        $workout->name = $routine->name.' · '.$day->format('d/m/Y');
        $workout->startedAt = $startedAt;

        foreach ($this->routineTree->orderedBlocks($routine) as $routineBlock) {
            $block = new WorkoutBlockDataModel();
            $block->workout = $workout;
            $block->position = $workout->nextBlockPosition();

            foreach ($this->routineTree->orderedExercises($routineBlock) as $routineExercise) {
                if (false === $routineExercise->movement->isOffered()) {
                    continue;
                }

                $block->exercises->add($this->exerciseFromRoutine($routineExercise, $block, $defaultSetType, $oneRepMaxes));
            }

            if (false === $block->exercises->isEmpty()) {
                $workout->blocks->add($block);
            }
        }

        return $workout;
    }

    /**
     * The movements of `$routine` that a start leaves out, each once, in the order they come.
     *
     * @return list<MovementDataModel>
     */
    public function movementsLeftOutOfRoutine(RoutineDataModel $routine): array
    {
        return array_values(array_filter(
            $this->routineTree->movements($routine),
            static fn (MovementDataModel $movement): bool => false === $movement->isOffered(),
        ));
    }

    /** @param array<int, float> $oneRepMaxes */
    private function exerciseFromRoutine(RoutineExerciseDataModel $source, WorkoutBlockDataModel $block, SetTypeDataModel $defaultSetType, array $oneRepMaxes): WorkoutExerciseDataModel
    {
        $exercise = new WorkoutExerciseDataModel();
        $exercise->block = $block;
        $exercise->movement = $source->movement;
        $exercise->position = $block->nextExercisePosition();
        $exercise->note = $source->note;
        $exercise->restInSeconds = $source->restInSeconds;

        $oneRepMax = $oneRepMaxes[$source->movement->id] ?? null;
        foreach ($this->routineTree->orderedSets($source) as $sourceSet) {
            $set = $this->setFromRoutine($sourceSet, $exercise, $defaultSetType, $oneRepMax);
            if (null !== $set) {
                $exercise->sets->add($set);
            }
        }

        return $exercise;
    }

    private function setFromRoutine(RoutineSetDataModel $source, WorkoutExerciseDataModel $exercise, SetTypeDataModel $defaultSetType, ?float $oneRepMax): ?WorkoutSetDataModel
    {
        $movement = $exercise->movement;

        $set = new WorkoutSetDataModel();
        $set->exercise = $exercise;
        $set->position = $exercise->nextSetPosition();
        $set->reps = true === $movement->tracksReps ? $source->reps : null;
        $set->weightInKilograms = true === $movement->tracksWeight ? $this->prescribedLoad->of($movement, $source->percentageOfOneRepMax, $oneRepMax) : null;
        $set->setType = $defaultSetType;
        $set->isComplete = false;

        $violations = WorkoutSetMeasuresConstraint::validate($movement, $set->reps, $set->weightInKilograms, $set->durationInSeconds, $set->distanceInMetres);

        return [] === $violations ? $set : null;
    }

    private function copyExercise(WorkoutExerciseDataModel $source, WorkoutBlockDataModel $block, SetTypeDataModel $defaultSetType): WorkoutExerciseDataModel
    {
        $exercise = new WorkoutExerciseDataModel();
        $exercise->block = $block;
        $exercise->movement = $source->movement;
        $exercise->position = $block->nextExercisePosition();
        $exercise->note = $source->note;
        $exercise->restInSeconds = $source->restInSeconds;

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
