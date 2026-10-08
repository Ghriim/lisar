<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Factory\OutputFactory\Training;

use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutSummaryDataModel;
use App\Domain\Factory\OutputFactory\Training\WorkoutSummaryOutputFactory;
use PHPUnit\Framework\TestCase;

final class WorkoutSummaryOutputFactoryTest extends TestCase
{
    use WorkoutOutputFactoriesTestTrait;

    public function testItNamesEachMovementOnceInWorkoutOrderAndReadsTheSummary(): void
    {
        $workout = $this->workout();
        $pushUp = $this->movement(1, 'Push-up');
        $last = $this->block($workout, 10, 1);
        $first = $this->block($workout, 11, 0);
        $this->set($this->exercise($first, 20, 0, $this->movement(2, 'Bench press')), 30, 0, 8);
        $this->set($this->exercise($first, 21, 1, $pushUp), 31, 0, 15);
        $this->set($this->exercise($last, 22, 0, $pushUp), 32, 0, 12);

        $output = (new WorkoutSummaryOutputFactory($this->mapper()))->buildOne($workout, $this->summary($workout, setCount: 3, personalBestCount: 2));

        self::assertSame(['Bench press', 'Push-up'], $output->movementNames);
        self::assertSame(3, $output->setCount);
        self::assertSame(480.0, $output->volumeInKilograms);
        self::assertNull($output->durationInSeconds);
        self::assertNull($output->distanceInMetres);
        self::assertSame(2, $output->personalBestCount);
        self::assertSame('2026-09-30T16:00:00+00:00', $output->startedAt);
    }

    public function testItWrapsAPageInTheEnvelope(): void
    {
        $second = $this->workout(2);
        $page = (new WorkoutSummaryOutputFactory($this->mapper()))->buildPaginated(
            [$this->workout(1), $second],
            [2 => $this->summary($second, setCount: 5, personalBestCount: 3)],
            7,
            2,
            2,
        );

        // A workout without a summary reads as empty.
        self::assertSame(0, $page->items[0]->setCount);
        self::assertSame(0, $page->items[0]->personalBestCount);
        self::assertSame(5, $page->items[1]->setCount);
        self::assertSame(3, $page->items[1]->personalBestCount);
        self::assertSame(7, $page->total);
        self::assertSame(2, $page->page);
        self::assertSame(2, $page->perPage);
    }

    private function summary(WorkoutDataModel $workout, int $setCount, int $personalBestCount): WorkoutSummaryDataModel
    {
        $summary = new WorkoutSummaryDataModel();
        $summary->workout = $workout;
        $summary->setCount = $setCount;
        $summary->volumeInKilograms = 480.0;
        $summary->personalBestCount = $personalBestCount;

        return $summary;
    }
}
