<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Factory\OutputFactory\Training;

use App\Domain\DTO\DataModel\Training\MuscleDataModel;
use App\Domain\Factory\OutputFactory\Training\WorkoutStatsOutputFactory;
use PHPUnit\Framework\TestCase;

final class WorkoutStatsOutputFactoryTest extends TestCase
{
    use WorkoutOutputFactoriesTestTrait;

    public function testAnEmptyWorkoutAmountsToNothing(): void
    {
        $stats = (new WorkoutStatsOutputFactory())->buildOne($this->workout(7));

        self::assertSame(7, $stats->workoutId);
        self::assertSame(0, $stats->setCount);
        self::assertNull($stats->volumeInKilograms);
        self::assertNull($stats->durationInSeconds);
        self::assertNull($stats->distanceInMetres);
        self::assertSame([], $stats->muscles);
    }

    /** No load, no time, no distance: those totals stay null, not zero. */
    public function testOnlyWhatTheSetsMeasureIsTotalled(): void
    {
        $workout = $this->workout();
        $pushUp = $this->movement(1, 'Push-up');
        $pushUp->primaryMuscle = $this->muscle(1, 'Mid chest');
        $exercise = $this->exercise($this->block($workout, 1, 0), 1, 0, $pushUp);
        $this->set($exercise, 1, 0, reps: 15);

        $stats = (new WorkoutStatsOutputFactory())->buildOne($workout);

        self::assertSame(1, $stats->setCount);
        self::assertNull($stats->volumeInKilograms);
        self::assertNull($stats->durationInSeconds);
        self::assertNull($stats->distanceInMetres);
    }

    /** Reps count per side on a unilateral movement: its volume counts twice. */
    public function testAUnilateralSetCountsBothSides(): void
    {
        $workout = $this->workout();
        $lunge = $this->movement(1, 'Lunge');
        $lunge->primaryMuscle = $this->muscle(1, 'Quads');
        $lunge->isUnilateral = true;
        $exercise = $this->exercise($this->block($workout, 1, 0), 1, 0, $lunge);
        $this->set($exercise, 1, 0, reps: 10)->weightInKilograms = 20.0;

        self::assertSame(400.0, (new WorkoutStatsOutputFactory())->buildOne($workout)->volumeInKilograms);
    }

    public function testDurationsAndDistancesAddUp(): void
    {
        $workout = $this->workout();
        $run = $this->movement(1, 'Run');
        $run->primaryMuscle = $this->muscle(1, 'Full body');
        $exercise = $this->exercise($this->block($workout, 1, 0), 1, 0, $run);
        foreach ([1, 2] as $id) {
            $set = $this->set($exercise, $id, $id, reps: 1);
            $set->reps = null;
            $set->durationInSeconds = 600;
            $set->distanceInMetres = 2000;
        }

        $stats = (new WorkoutStatsOutputFactory())->buildOne($workout);

        self::assertSame(1200, $stats->durationInSeconds);
        self::assertSame(4000, $stats->distanceInMetres);
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

        $muscles = (new WorkoutStatsOutputFactory())->buildOne($workout)->muscles;

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
}
