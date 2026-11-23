<?php

declare(strict_types=1);

namespace App\Domain\Training;

use App\Domain\DTO\DataModel\Training\MovementDataModel;
use App\Domain\DTO\DataModel\Training\RoutineBlockDataModel;
use App\Domain\DTO\DataModel\Training\RoutineDataModel;
use App\Domain\DTO\DataModel\Training\RoutineExerciseDataModel;
use App\Domain\DTO\DataModel\Training\RoutineSetDataModel;

use function in_array;

/**
 * Reads a routine's tree: its blocks, movements and sets in order, where the next of each goes,
 * and the movements it holds. The collections come back ordered from the database, but not once a
 * request has added to them or moved a block: the order is worked out here, from the positions.
 */
final readonly class RoutineTree
{
    /** @return list<RoutineBlockDataModel> in routine order */
    public function orderedBlocks(RoutineDataModel $routine): array
    {
        $blocks = array_values($routine->blocks->toArray());
        usort($blocks, static fn (RoutineBlockDataModel $a, RoutineBlockDataModel $b): int => [$a->position, $a->id] <=> [$b->position, $b->id]);

        return $blocks;
    }

    /** @return list<RoutineExerciseDataModel> in block order */
    public function orderedExercises(RoutineBlockDataModel $block): array
    {
        $exercises = array_values($block->exercises->toArray());
        usort($exercises, static fn (RoutineExerciseDataModel $a, RoutineExerciseDataModel $b): int => [$a->position, $a->id] <=> [$b->position, $b->id]);

        return $exercises;
    }

    /** @return list<RoutineSetDataModel> in the order they were added */
    public function orderedSets(RoutineExerciseDataModel $exercise): array
    {
        $sets = array_values($exercise->sets->toArray());
        usort($sets, static fn (RoutineSetDataModel $a, RoutineSetDataModel $b): int => [$a->position, $a->id] <=> [$b->position, $b->id]);

        return $sets;
    }

    /** The position the next block takes: after every one already there. */
    public function nextBlockPosition(RoutineDataModel $routine): int
    {
        $position = 0;
        foreach ($routine->blocks as $block) {
            $position = max($position, $block->position + 1);
        }

        return $position;
    }

    /** The position the next movement of the block takes: after every one already there. */
    public function nextExercisePosition(RoutineBlockDataModel $block): int
    {
        $position = 0;
        foreach ($block->exercises as $exercise) {
            $position = max($position, $exercise->position + 1);
        }

        return $position;
    }

    /** The position the next set of the movement takes: after every one already there. */
    public function nextSetPosition(RoutineExerciseDataModel $exercise): int
    {
        $position = 0;
        foreach ($exercise->sets as $set) {
            $position = max($position, $set->position + 1);
        }

        return $position;
    }

    /**
     * The movements the routine holds, each once, in the order they come.
     *
     * @return list<MovementDataModel>
     */
    public function movements(RoutineDataModel $routine): array
    {
        $movements = [];
        foreach ($this->orderedBlocks($routine) as $block) {
            foreach ($this->orderedExercises($block) as $exercise) {
                if (false === in_array($exercise->movement, $movements, true)) {
                    $movements[] = $exercise->movement;
                }
            }
        }

        return $movements;
    }

    /** How many movements it holds, a movement that comes twice counting twice. */
    public function countExercises(RoutineDataModel $routine): int
    {
        $count = 0;
        foreach ($routine->blocks as $block) {
            $count += $block->exercises->count();
        }

        return $count;
    }
}
