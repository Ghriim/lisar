<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Factory\OutputFactory\Training;

use App\Domain\Factory\OutputFactory\Training\WorkoutSummaryOutputFactory;
use PHPUnit\Framework\TestCase;

final class WorkoutSummaryOutputFactoryTest extends TestCase
{
    use WorkoutOutputFactoriesTestTrait;

    public function testItNamesEachMovementOnceInWorkoutOrderAndCountsTheSets(): void
    {
        $workout = $this->workout();
        $pushUp = $this->movement(1, 'Push-up');
        $last = $this->block($workout, 10, 1);
        $first = $this->block($workout, 11, 0);
        $this->set($this->exercise($first, 20, 0, $this->movement(2, 'Bench press')), 30, 0, 8);
        $this->set($this->exercise($first, 21, 1, $pushUp), 31, 0, 15);
        $this->set($this->exercise($last, 22, 0, $pushUp), 32, 0, 12);

        $output = (new WorkoutSummaryOutputFactory($this->mapper()))->buildOne($workout);

        self::assertSame(['Bench press', 'Push-up'], $output->movementNames);
        self::assertSame(3, $output->setCount);
        self::assertSame('2026-09-30T16:00:00+00:00', $output->startedAt);
    }

    public function testItWrapsAPageInTheEnvelope(): void
    {
        $page = (new WorkoutSummaryOutputFactory($this->mapper()))->buildPaginated([$this->workout(1), $this->workout(2)], 7, 2, 2);

        self::assertCount(2, $page->items);
        self::assertSame(7, $page->total);
        self::assertSame(2, $page->page);
        self::assertSame(2, $page->perPage);
    }
}
