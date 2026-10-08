<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Factory\OutputFactory\Training;

use App\Domain\DTO\DataModel\Training\MuscleDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutSummaryDataModel;
use App\Domain\Factory\OutputFactory\Training\PersonalBestOutputFactory;
use App\Domain\Factory\OutputFactory\Training\WorkoutStatsOutputFactory;
use PHPUnit\Framework\TestCase;

final class WorkoutStatsOutputFactoryTest extends TestCase
{
    use WorkoutOutputFactoriesTestTrait;

    /** No summary to read: the totals are those of an empty workout. */
    public function testAWorkoutWithoutSummaryAmountsToNothing(): void
    {
        $stats = $this->factory()->buildOne($this->workout(7), null);

        self::assertSame(7, $stats->workoutId);
        self::assertSame(0, $stats->setCount);
        self::assertNull($stats->volumeInKilograms);
        self::assertNull($stats->durationInSeconds);
        self::assertNull($stats->distanceInMetres);
        self::assertSame([], $stats->muscles);
    }

    /** The totals are read off the summary as it stands, not worked out again from the sets. */
    public function testTheTotalsAreTheSummarys(): void
    {
        $workout = $this->workout();
        $summary = new WorkoutSummaryDataModel();
        $summary->workout = $workout;
        $summary->setCount = 4;
        $summary->volumeInKilograms = 1780.0;
        $summary->distanceInMetres = 80;

        $stats = $this->factory()->buildOne($workout, $summary);

        self::assertSame(4, $stats->setCount);
        self::assertSame(1780.0, $stats->volumeInKilograms);
        self::assertNull($stats->durationInSeconds);
        self::assertSame(80, $stats->distanceInMetres);
    }

    /** One for the primary muscle, a half for each secondary one; the most worked first. */
    public function testItSharesTheSetsOutBetweenTheMuscles(): void
    {
        $workout = $this->workout();
        $bench = $this->movement(1, 'Bench press');
        $bench->primaryMuscle = $this->muscle(1, 'Mid chest');
        $bench->secondaryMuscles->add($this->muscle(3, 'Triceps'));
        $bench->secondaryMuscles->add($this->muscle(2, 'Front delts'));
        $exercise = $this->exercise($this->block($workout, 1, 0), 1, 0, $bench);
        $this->set($exercise, 1, 0, reps: 8);
        $this->set($exercise, 2, 1, reps: 8);

        $muscles = $this->factory()->buildOne($workout, null)->muscles;

        self::assertSame(
            [['Mid chest', 2.0, 50.0], ['Front delts', 1.0, 25.0], ['Triceps', 1.0, 25.0]],
            array_map(static fn ($muscle) => [$muscle->muscleName, $muscle->setShare, $muscle->percentage], $muscles),
        );
        self::assertSame(1, $muscles[0]->muscleId);
    }

    private function muscle(int $id, string $name): MuscleDataModel
    {
        $muscle = new MuscleDataModel();
        $muscle->id = $id;
        $muscle->name = $name;

        return $muscle;
    }

    private function factory(): WorkoutStatsOutputFactory
    {
        return new WorkoutStatsOutputFactory(new PersonalBestOutputFactory($this->mapper()));
    }
}
