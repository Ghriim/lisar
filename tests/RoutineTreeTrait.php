<?php

declare(strict_types=1);

namespace App\Tests;

use App\Domain\DTO\DataModel\Training\MovementDataModel;
use App\Domain\DTO\DataModel\Training\RoutineBlockDataModel;
use App\Domain\DTO\DataModel\Training\RoutineDataModel;
use App\Domain\DTO\DataModel\Training\RoutineExerciseDataModel;
use App\Domain\DTO\DataModel\Training\RoutineSetDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;

/**
 * A routine and what a route names inside it — a block, a movement, a set — built by hand and hung
 * under the routine given, the way Doctrine would hand it back.
 */
trait RoutineTreeTrait
{
    private function routineOf(?UserDataModel $owner): RoutineDataModel
    {
        $routine = new RoutineDataModel();
        $routine->owner = $owner;
        $routine->name = 'Push';

        return $routine;
    }

    private function routineBlockIn(RoutineDataModel $routine, int $position = 0, ?int $id = null): RoutineBlockDataModel
    {
        $block = new RoutineBlockDataModel();
        $block->id = $id;
        $block->routine = $routine;
        $block->position = $position;
        $routine->blocks->add($block);

        return $block;
    }

    private function routineExerciseIn(RoutineBlockDataModel $block, ?MovementDataModel $movement = null, int $position = 0, ?int $id = null): RoutineExerciseDataModel
    {
        $exercise = new RoutineExerciseDataModel();
        $exercise->id = $id;
        $exercise->block = $block;
        $exercise->movement = $movement ?? $this->barbellMovement();
        $exercise->position = $position;
        $block->exercises->add($exercise);

        return $exercise;
    }

    private function routineSetIn(RoutineExerciseDataModel $exercise, int $position = 0, ?int $reps = 5, ?float $percentage = 80.0, ?int $id = null): RoutineSetDataModel
    {
        $set = new RoutineSetDataModel();
        $set->id = $id;
        $set->exercise = $exercise;
        $set->position = $position;
        $set->reps = $reps;
        $set->percentageOfOneRepMax = $percentage;
        $exercise->sets->add($set);

        return $set;
    }

    /** Reps and a load: a bench press. */
    private function barbellMovement(int $id = 1, bool $isUnilateral = false): MovementDataModel
    {
        $movement = new MovementDataModel();
        $movement->id = $id;
        $movement->name = 'Bench press (barbell)';
        $movement->tracksReps = true;
        $movement->tracksWeight = true;
        $movement->isUnilateral = $isUnilateral;

        return $movement;
    }

    /** Reps alone: a push-up. */
    private function bodyweightMovement(int $id = 2): MovementDataModel
    {
        $movement = new MovementDataModel();
        $movement->id = $id;
        $movement->name = 'Push-up';
        $movement->tracksReps = true;

        return $movement;
    }

    /** A load over a distance: a farmer walk, which a routine cannot prescribe yet. */
    private function carryMovement(int $id = 3): MovementDataModel
    {
        $movement = new MovementDataModel();
        $movement->id = $id;
        $movement->name = 'Farmer walk (dumbbell)';
        $movement->tracksWeight = true;
        $movement->tracksDistance = true;

        return $movement;
    }
}
