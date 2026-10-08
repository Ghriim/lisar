<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Factory\DataModelFactory\Training;

use App\Domain\DTO\DataModel\Training\WorkoutExerciseDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutSetDataModel;
use App\Domain\Factory\DataModelFactory\Training\WorkoutSummaryDataModelFactory;
use App\Tests\Unit\Domain\Factory\OutputFactory\Training\WorkoutOutputFactoriesTestTrait;
use PHPUnit\Framework\TestCase;

final class WorkoutSummaryDataModelFactoryTest extends TestCase
{
    use WorkoutOutputFactoriesTestTrait;

    public function testAnEmptyWorkoutAddsUpToNothing(): void
    {
        $workout = $this->workout();

        $summary = (new WorkoutSummaryDataModelFactory())->build($workout);

        self::assertSame($workout, $summary->workout);
        self::assertSame(0, $summary->setCount);
        self::assertNull($summary->volumeInKilograms);
        self::assertNull($summary->durationInSeconds);
        self::assertNull($summary->distanceInMetres);
        self::assertSame(0, $summary->personalBestCount);
    }

    /** No load, no time, no distance: those totals stay null, not zero. */
    public function testOnlyWhatTheSetsMeasureIsTotalled(): void
    {
        $workout = $this->workout();
        $this->doneSet($this->exercise($this->block($workout, 1, 0), 1, 0, $this->movement(1, 'Push-up')), 1, reps: 15);

        $summary = (new WorkoutSummaryDataModelFactory())->build($workout);

        self::assertSame(1, $summary->setCount);
        self::assertNull($summary->volumeInKilograms);
        self::assertNull($summary->durationInSeconds);
        self::assertNull($summary->distanceInMetres);
    }

    /** A set still to do has added nothing yet. */
    public function testASetNotDoneCountsForNothing(): void
    {
        $workout = $this->workout();
        $exercise = $this->exercise($this->block($workout, 1, 0), 1, 0, $this->movement(1, 'Bench press'));
        $this->doneSet($exercise, 1, reps: 8)->weightInKilograms = 60.0;
        $this->set($exercise, 2, 1, reps: 8)->weightInKilograms = 60.0;

        $summary = (new WorkoutSummaryDataModelFactory())->build($workout);

        self::assertSame(1, $summary->setCount);
        self::assertSame(480.0, $summary->volumeInKilograms);
    }

    /** Reps count per side on a unilateral movement: its volume counts twice. */
    public function testAUnilateralSetCountsBothSides(): void
    {
        $workout = $this->workout();
        $lunge = $this->movement(1, 'Lunge');
        $lunge->isUnilateral = true;
        $this->doneSet($this->exercise($this->block($workout, 1, 0), 1, 0, $lunge), 1, reps: 10)->weightInKilograms = 20.0;

        self::assertSame(400.0, (new WorkoutSummaryDataModelFactory())->build($workout)->volumeInKilograms);
    }

    public function testDurationsAndDistancesAddUp(): void
    {
        $workout = $this->workout();
        $exercise = $this->exercise($this->block($workout, 1, 0), 1, 0, $this->movement(1, 'Run'));
        foreach ([1, 2] as $id) {
            $set = $this->doneSet($exercise, $id, reps: 1);
            $set->reps = null;
            $set->durationInSeconds = 600;
            $set->distanceInMetres = 2000;
        }

        $summary = (new WorkoutSummaryDataModelFactory())->build($workout);

        self::assertSame(1200, $summary->durationInSeconds);
        self::assertSame(4000, $summary->distanceInMetres);
    }

    /** The record count is the records' rebuild's to move: working the totals out leaves it. */
    public function testRefreshingLeavesTheRecordCount(): void
    {
        $workout = $this->workout();
        $factory = new WorkoutSummaryDataModelFactory();
        $summary = $factory->build($workout);
        $summary->personalBestCount = 3;
        $this->doneSet($this->exercise($this->block($workout, 1, 0), 1, 0, $this->movement(1, 'Push-up')), 1, reps: 15);

        $factory->refresh($summary);

        self::assertSame(1, $summary->setCount);
        self::assertSame(3, $summary->personalBestCount);
    }

    private function doneSet(WorkoutExerciseDataModel $exercise, int $id, int $reps): WorkoutSetDataModel
    {
        $set = $this->set($exercise, $id, $id, $reps);
        $set->isComplete = true;

        return $set;
    }
}
