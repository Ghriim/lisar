<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Factory\OutputFactory;

use App\Domain\DTO\DataModel\MovementDataModel;
use App\Domain\DTO\DataModel\WorkoutBlockDataModel;
use App\Domain\DTO\DataModel\WorkoutDataModel;
use App\Domain\DTO\DataModel\WorkoutExerciseDataModel;
use App\Domain\DTO\DataModel\WorkoutSetDataModel;
use App\Domain\Factory\OutputFactory\SetTypeOutputFactory;
use App\Domain\Factory\OutputFactory\WorkoutSetOutputFactory;
use DateTimeImmutable;
use Symfony\Component\ObjectMapper\ObjectMapper;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

/** Builds workouts by hand for the workout output factories' tests. */
trait WorkoutOutputFactoriesTestTrait
{
    private function mapper(): ObjectMapperInterface
    {
        return new ObjectMapper(propertyAccessor: PropertyAccess::createPropertyAccessor());
    }

    private function setFactory(): WorkoutSetOutputFactory
    {
        return new WorkoutSetOutputFactory($this->mapper(), new SetTypeOutputFactory($this->mapper()));
    }

    private function workout(int $id = 1): WorkoutDataModel
    {
        $workout = new WorkoutDataModel();
        $workout->id = $id;
        $workout->startedAt = new DateTimeImmutable('2026-09-30T16:00:00+00:00');

        return $workout;
    }

    private function block(WorkoutDataModel $workout, int $id, int $position): WorkoutBlockDataModel
    {
        $block = new WorkoutBlockDataModel();
        $block->id = $id;
        $block->position = $position;
        $block->workout = $workout;
        $workout->blocks->add($block);

        return $block;
    }

    private function exercise(WorkoutBlockDataModel $block, int $id, int $position, MovementDataModel $movement): WorkoutExerciseDataModel
    {
        $exercise = new WorkoutExerciseDataModel();
        $exercise->id = $id;
        $exercise->position = $position;
        $exercise->movement = $movement;
        $exercise->block = $block;
        $block->exercises->add($exercise);

        return $exercise;
    }

    private function set(WorkoutExerciseDataModel $exercise, int $id, int $position, int $reps): WorkoutSetDataModel
    {
        $set = new WorkoutSetDataModel();
        $set->id = $id;
        $set->position = $position;
        $set->reps = $reps;
        $set->exercise = $exercise;
        $exercise->sets->add($set);

        return $set;
    }

    private function movement(int $id, string $name): MovementDataModel
    {
        $movement = new MovementDataModel();
        $movement->id = $id;
        $movement->name = $name;
        $movement->tracksReps = true;

        return $movement;
    }
}
