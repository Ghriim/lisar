<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Factory\OutputFactory;

use App\Domain\Factory\OutputFactory\WorkoutPreviousPerformanceOutputFactory;
use PHPUnit\Framework\TestCase;

final class WorkoutPreviousPerformanceOutputFactoryTest extends TestCase
{
    use WorkoutOutputFactoriesTestTrait;

    /** A movement that came twice in that workout gives the sets of both times, in order. */
    public function testItGathersTheMovementsSetsAcrossTheWorkout(): void
    {
        $pushUp = $this->movement(1, 'Push-up');
        $workout = $this->workout(9);
        $second = $this->block($workout, 10, 1);
        $first = $this->block($workout, 11, 0);
        $this->set($this->exercise($second, 20, 0, $pushUp), 30, 0, 10);
        $this->set($this->exercise($first, 21, 0, $pushUp), 31, 0, 15);
        $this->set($this->exercise($first, 22, 1, $this->movement(2, 'Row')), 32, 0, 8);

        $outputs = (new WorkoutPreviousPerformanceOutputFactory($this->setFactory()))->buildMany([1 => [$pushUp, $workout]]);

        self::assertCount(1, $outputs);
        self::assertSame(1, $outputs[0]->movementId);
        self::assertSame(9, $outputs[0]->workoutId);
        self::assertSame('2026-09-30T16:00:00+00:00', $outputs[0]->startedAt);
        self::assertSame([15, 10], array_map(static fn ($set) => $set->reps, $outputs[0]->sets));
    }
}
