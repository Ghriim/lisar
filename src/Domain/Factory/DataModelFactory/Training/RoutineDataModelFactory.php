<?php

declare(strict_types=1);

namespace App\Domain\Factory\DataModelFactory\Training;

use App\Domain\DTO\DataModel\Training\RoutineBlockDataModel;
use App\Domain\DTO\DataModel\Training\RoutineDataModel;
use App\Domain\DTO\DataModel\Training\RoutineExerciseDataModel;
use App\Domain\DTO\DataModel\Training\RoutineSetDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Training\RoutineTree;

final readonly class RoutineDataModelFactory
{
    public const string COPY_PREFIX = 'Copie de ';
    public const int NAME_MAX_LENGTH = 100;

    public function __construct(
        private RoutineTree $routineTree,
    ) {
    }

    /**
     * A new routine of `$owner`'s, « Copie de Push », cut at the name's length, holding the same
     * blocks, movements, notes, rests and sets in the same order. A movement retired since comes
     * along: the copy is a routine like its source, and starting it leaves that movement out the
     * same way.
     */
    public function buildCopy(RoutineDataModel $source, UserDataModel $owner): RoutineDataModel
    {
        $routine = new RoutineDataModel();
        $routine->owner = $owner;
        $routine->name = mb_substr(self::COPY_PREFIX.$source->name, 0, self::NAME_MAX_LENGTH);

        foreach ($this->routineTree->orderedBlocks($source) as $sourceBlock) {
            $block = new RoutineBlockDataModel();
            $block->routine = $routine;
            $block->position = $this->routineTree->nextBlockPosition($routine);

            foreach ($this->routineTree->orderedExercises($sourceBlock) as $sourceExercise) {
                $block->exercises->add($this->copyExercise($sourceExercise, $block));
            }

            $routine->blocks->add($block);
        }

        return $routine;
    }

    private function copyExercise(RoutineExerciseDataModel $source, RoutineBlockDataModel $block): RoutineExerciseDataModel
    {
        $exercise = new RoutineExerciseDataModel();
        $exercise->block = $block;
        $exercise->movement = $source->movement;
        $exercise->position = $this->routineTree->nextExercisePosition($block);
        $exercise->note = $source->note;
        $exercise->restInSeconds = $source->restInSeconds;

        foreach ($this->routineTree->orderedSets($source) as $sourceSet) {
            $set = new RoutineSetDataModel();
            $set->exercise = $exercise;
            $set->position = $this->routineTree->nextSetPosition($exercise);
            $set->reps = $sourceSet->reps;
            $set->percentageOfOneRepMax = $sourceSet->percentageOfOneRepMax;
            $exercise->sets->add($set);
        }

        return $exercise;
    }
}
