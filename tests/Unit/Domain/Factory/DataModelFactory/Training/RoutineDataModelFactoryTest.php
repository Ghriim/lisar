<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Factory\DataModelFactory\Training;

use App\Domain\DTO\DataModel\Training\RoutineBlockDataModel;
use App\Domain\DTO\DataModel\Training\RoutineDataModel;
use App\Domain\DTO\DataModel\Training\RoutineExerciseDataModel;
use App\Domain\DTO\DataModel\Training\RoutineSetDataModel;
use App\Domain\Factory\DataModelFactory\Training\RoutineDataModelFactory;
use App\Domain\Training\RoutineTree;
use App\Tests\CurrentUserTrait;
use App\Tests\RoutineTreeTrait;
use PHPUnit\Framework\TestCase;

final class RoutineDataModelFactoryTest extends TestCase
{
    use CurrentUserTrait;
    use RoutineTreeTrait;

    private RoutineDataModelFactory $factory;
    private RoutineTree $tree;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tree = new RoutineTree();
        $this->factory = new RoutineDataModelFactory($this->tree);
    }

    public function testItCopiesEverythingInOrder(): void
    {
        $source = $this->routineOf($this->currentUser(1));
        // Built out of order, as positions with gaps: the copy follows the positions.
        $second = $this->routineBlockIn($source, position: 5, id: 2);
        $first = $this->routineBlockIn($source, position: 1, id: 1);
        $bench = $this->routineExerciseIn($first, $this->barbellMovement());
        $bench->note = 'Prise serrée';
        $bench->restInSeconds = 150;
        $this->routineSetIn($bench, position: 3, reps: 3, percentage: 85.0);
        $this->routineSetIn($bench, position: 0, reps: 5, percentage: 80.0);
        $this->routineExerciseIn($second, $this->bodyweightMovement(), position: 2);
        $this->routineExerciseIn($second, $this->barbellMovement(4), position: 0);

        $owner = $this->currentUser(7);
        $copy = $this->factory->buildCopy($source, $owner);

        self::assertSame($owner, $copy->owner);
        self::assertSame('Copie de Push', $copy->name);
        self::assertSame([[1], [4, 2]], $this->layout($copy));

        $copiedBench = $this->tree->orderedExercises($this->tree->orderedBlocks($copy)[0])[0];
        self::assertSame('Prise serrée', $copiedBench->note);
        self::assertSame(150, $copiedBench->restInSeconds);
        self::assertSame(
            [[5, 80.0], [3, 85.0]],
            array_map(static fn (RoutineSetDataModel $set) => [$set->reps, $set->percentageOfOneRepMax], $this->tree->orderedSets($copiedBench)),
        );
        // New rows, hung under the copy: nothing of the source is moved.
        self::assertNull($copiedBench->id);
        self::assertSame($copy, $copiedBench->block->routine);
        self::assertCount(2, $source->blocks);
    }

    public function testANameThatWouldRunPastItsLengthIsCut(): void
    {
        $source = $this->routineOf($this->currentUser(1));
        $source->name = str_repeat('é', RoutineDataModelFactory::NAME_MAX_LENGTH);

        $copy = $this->factory->buildCopy($source, $this->currentUser(1));

        self::assertSame(RoutineDataModelFactory::NAME_MAX_LENGTH, mb_strlen($copy->name));
        self::assertStringStartsWith('Copie de éé', $copy->name);
    }

    public function testAnEmptyRoutineGivesAnEmptyCopy(): void
    {
        $copy = $this->factory->buildCopy($this->routineOf($this->currentUser(1)), $this->currentUser(1));

        self::assertTrue($copy->blocks->isEmpty());
    }

    /** @return list<list<int|null>> each block's movement ids, in order */
    private function layout(RoutineDataModel $routine): array
    {
        return array_map(
            fn (RoutineBlockDataModel $block) => array_map(static fn (RoutineExerciseDataModel $exercise) => $exercise->movement->id, $this->tree->orderedExercises($block)),
            $this->tree->orderedBlocks($routine),
        );
    }
}
