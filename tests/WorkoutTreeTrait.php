<?php

declare(strict_types=1);

namespace App\Tests;

use App\Domain\DTO\DataModel\Training\MovementDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutBlockDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutExerciseDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutSetDataModel;

/**
 * What a route names inside a workout — a block, a movement, a set — built by hand and hung under
 * the workout given, the way Doctrine would hand it back.
 */
trait WorkoutTreeTrait
{
    private function blockIn(WorkoutDataModel $workout): WorkoutBlockDataModel
    {
        $block = new WorkoutBlockDataModel();
        $block->workout = $workout;
        $workout->blocks->add($block);

        return $block;
    }

    private function exerciseIn(WorkoutDataModel $workout, ?MovementDataModel $movement = null): WorkoutExerciseDataModel
    {
        $block = $this->blockIn($workout);
        $exercise = new WorkoutExerciseDataModel();
        $exercise->block = $block;
        $exercise->movement = $movement ?? new MovementDataModel();
        $block->exercises->add($exercise);

        return $exercise;
    }

    private function setIn(WorkoutDataModel $workout, ?WorkoutExerciseDataModel $exercise = null): WorkoutSetDataModel
    {
        $exercise ??= $this->exerciseIn($workout);
        $set = new WorkoutSetDataModel();
        $set->exercise = $exercise;
        $exercise->sets->add($set);

        return $set;
    }
}
