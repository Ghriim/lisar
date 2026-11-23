<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Factory\OutputFactory\Training;

use App\Domain\DTO\Output\Training\RoutineSetDataOutput;
use App\Domain\Factory\OutputFactory\Training\RoutineOutputFactory;
use App\Domain\Training\PrescribedLoad;
use App\Domain\Training\RoutineTree;
use App\Tests\CurrentUserTrait;
use App\Tests\RoutineTreeTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\ObjectMapper\ObjectMapper;
use Symfony\Component\PropertyAccess\PropertyAccess;

final class RoutineOutputFactoryTest extends TestCase
{
    use CurrentUserTrait;
    use RoutineTreeTrait;

    private RoutineOutputFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new RoutineOutputFactory(new ObjectMapper(propertyAccessor: PropertyAccess::createPropertyAccessor()), new RoutineTree(), new PrescribedLoad());
    }

    public function testItBuildsAnEmptyRoutine(): void
    {
        $routine = $this->routineOf($this->anyCurrentUser());
        $routine->id = 7;

        $output = $this->factory->buildOne($routine, []);

        self::assertSame(7, $output->id);
        self::assertSame('Push', $output->name);
        self::assertSame([], $output->blocks);
    }

    /** By position, whatever order the collections hold them in — a reorder leaves them stale. */
    public function testBlocksExercisesAndSetsComeByPosition(): void
    {
        $routine = $this->routineOf($this->anyCurrentUser());
        $routine->id = 7;
        $second = $this->routineBlockIn($routine, 1, 10);
        $first = $this->routineBlockIn($routine, 0, 11);
        $this->routineExerciseIn($second, $this->barbellMovement(1), 0, 20);
        $pushUp = $this->routineExerciseIn($first, $this->bodyweightMovement(2), 0, 21);
        $pushUp->note = 'Lent';
        $pushUp->restInSeconds = 60;
        $this->routineSetIn($pushUp, 1, 10, null, 31);
        $this->routineSetIn($pushUp, 0, 12, null, 30);

        $output = $this->factory->buildOne($routine, []);

        self::assertSame([11, 10], [$output->blocks[0]->id, $output->blocks[1]->id]);
        $exercise = $output->blocks[0]->exercises[0];
        self::assertSame('Push-up', $exercise->movement->name);
        self::assertSame('Lent', $exercise->note);
        self::assertSame(60, $exercise->restInSeconds);
        self::assertSame([12, 10], array_map(static fn (RoutineSetDataOutput $set): ?int => $set->reps, $exercise->sets));
    }

    /** The load today: the share of the caller's estimated 1RM, rounded down; none without one. */
    public function testEachSetCarriesTheLoadItWouldGiveToday(): void
    {
        $routine = $this->routineOf($this->anyCurrentUser());
        $routine->id = 7;
        $block = $this->routineBlockIn($routine, 0, 10);
        $bench = $this->routineExerciseIn($block, $this->barbellMovement(1), 0, 20);
        $this->routineSetIn($bench, 0, 5, 80.0, 30);
        $row = $this->routineExerciseIn($block, $this->barbellMovement(4, isUnilateral: true), 1, 21);
        $this->routineSetIn($row, 0, 8, 75.0, 31);
        $squat = $this->routineExerciseIn($block, $this->barbellMovement(5), 2, 22);
        $this->routineSetIn($squat, 0, 5, 80.0, 32);

        $output = $this->factory->buildOne($routine, [1 => 92.0, 4 => 31.0]);

        self::assertSame(80.0, $output->blocks[0]->exercises[0]->sets[0]->percentageOfOneRepMax);
        self::assertSame(73.0, $output->blocks[0]->exercises[0]->sets[0]->estimatedLoadInKilograms);
        self::assertSame(23.0, $output->blocks[0]->exercises[1]->sets[0]->estimatedLoadInKilograms);
        // Never done with a load: no estimated 1RM, no load yet.
        self::assertNull($output->blocks[0]->exercises[2]->sets[0]->estimatedLoadInKilograms);
    }
}
