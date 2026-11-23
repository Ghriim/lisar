<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Training;

use App\Domain\DTO\DataModel\Training\RoutineBlockDataModel;
use App\Domain\DTO\DataModel\Training\RoutineSetDataModel;
use App\Domain\Training\RoutineTree;
use App\Tests\CurrentUserTrait;
use App\Tests\RoutineTreeTrait;
use PHPUnit\Framework\TestCase;

final class RoutineTreeTest extends TestCase
{
    use CurrentUserTrait;
    use RoutineTreeTrait;

    private RoutineTree $tree;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tree = new RoutineTree();
    }

    /** By position, whatever order the collections hold them in — a reorder leaves them stale. */
    public function testBlocksExercisesAndSetsComeByPosition(): void
    {
        $routine = $this->routineOf($this->anyCurrentUser());
        $second = $this->routineBlockIn($routine, 1, 10);
        $first = $this->routineBlockIn($routine, 0, 11);
        $row = $this->routineExerciseIn($first, $this->barbellMovement(1), 1, 20);
        $bench = $this->routineExerciseIn($first, $this->barbellMovement(2), 0, 21);
        $this->routineSetIn($bench, 1, id: 30);
        $this->routineSetIn($bench, 0, id: 31);

        self::assertSame([11, 10], array_map(static fn (RoutineBlockDataModel $block): ?int => $block->id, $this->tree->orderedBlocks($routine)));
        self::assertSame([$bench, $row], $this->tree->orderedExercises($first));
        self::assertSame([31, 30], array_map(static fn (RoutineSetDataModel $set): ?int => $set->id, $this->tree->orderedSets($bench)));
        self::assertSame([], $this->tree->orderedExercises($second));
    }

    /** After every one already there, gaps and all; the first is 0. */
    public function testTheNextOneGoesAfterEveryOneAlreadyThere(): void
    {
        $routine = $this->routineOf($this->anyCurrentUser());
        self::assertSame(0, $this->tree->nextBlockPosition($routine));

        $block = $this->routineBlockIn($routine, 4);
        $this->routineBlockIn($routine, 1);
        $exercise = $this->routineExerciseIn($block, position: 2);
        $this->routineSetIn($exercise, 7);

        self::assertSame(5, $this->tree->nextBlockPosition($routine));
        self::assertSame(3, $this->tree->nextExercisePosition($block));
        self::assertSame(8, $this->tree->nextSetPosition($exercise));
    }

    /** Each movement once, in routine order; counted, a movement that comes twice counts twice. */
    public function testItReadsTheMovementsItHolds(): void
    {
        $routine = $this->routineOf($this->anyCurrentUser());
        $bench = $this->barbellMovement(1);
        $pushUp = $this->bodyweightMovement(2);
        $second = $this->routineBlockIn($routine, 1);
        $first = $this->routineBlockIn($routine, 0);
        $this->routineExerciseIn($first, $pushUp);
        $this->routineExerciseIn($second, $bench);
        $this->routineExerciseIn($second, $pushUp, 1);

        self::assertSame([$pushUp, $bench], $this->tree->movements($routine));
        self::assertSame(3, $this->tree->countExercises($routine));
    }

    public function testAnEmptyRoutineHoldsNothing(): void
    {
        $routine = $this->routineOf($this->anyCurrentUser());

        self::assertSame([], $this->tree->movements($routine));
        self::assertSame(0, $this->tree->countExercises($routine));
    }
}
