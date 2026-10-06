<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Factory\OutputFactory;

use App\Domain\Factory\OutputFactory\WorkoutOutputFactory;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class WorkoutOutputFactoryTest extends TestCase
{
    use WorkoutOutputFactoriesTestTrait;

    private WorkoutOutputFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new WorkoutOutputFactory($this->mapper(), $this->setFactory());
    }

    public function testItBuildsAWorkoutInProgress(): void
    {
        $workout = $this->workout();
        $workout->name = 'Push';
        $workout->feeling = 4;

        $output = $this->factory->buildOne($workout);

        self::assertSame(1, $output->id);
        self::assertSame('Push', $output->name);
        self::assertSame(4, $output->feeling);
        self::assertSame('2026-09-30T16:00:00+00:00', $output->startedAt);
        self::assertNull($output->finishedAt);
        self::assertTrue($output->isInProgress);
        self::assertSame([], $output->blocks);
    }

    public function testAFinishedWorkoutIsNoLongerInProgress(): void
    {
        $workout = $this->workout();
        $workout->finishedAt = new DateTimeImmutable('2026-09-30T17:00:00+00:00');

        $output = $this->factory->buildOne($workout);

        self::assertFalse($output->isInProgress);
        self::assertSame('2026-09-30T17:00:00+00:00', $output->finishedAt);
    }

    /** By position, whatever order the collections hold them in — a reorder leaves them stale. */
    public function testBlocksExercisesAndSetsComeByPosition(): void
    {
        $workout = $this->workout();
        $second = $this->block($workout, 10, 1);
        $first = $this->block($workout, 11, 0);
        $this->exercise($second, 20, 0, $this->movement(1, 'Bench press'));
        $row = $this->exercise($first, 21, 1, $this->movement(2, 'Row'));
        $pushUp = $this->exercise($first, 22, 0, $this->movement(3, 'Push-up'));
        $this->set($pushUp, 30, 1, 12);
        $this->set($pushUp, 31, 0, 15);
        $this->set($row, 32, 0, 10);

        $output = $this->factory->buildOne($workout);

        self::assertSame([11, 10], array_map(static fn ($block) => $block->id, $output->blocks));
        self::assertSame(['Push-up', 'Row'], array_map(static fn ($exercise) => $exercise->movement->name, $output->blocks[0]->exercises));
        self::assertSame([15, 12], array_map(static fn ($set) => $set->reps, $output->blocks[0]->exercises[0]->sets));
        self::assertTrue($output->blocks[0]->exercises[0]->movement->tracksReps);
    }
}
