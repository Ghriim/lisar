<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Factory\DataModelFactory\Training;

use App\Domain\DTO\Aggregate\Training\WorkoutTally;
use App\Domain\DTO\DataModel\Training\MovementDataModel;
use App\Domain\DTO\DataModel\Training\PersonalBestDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutBlockDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutExerciseDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutSetDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Factory\DataModelFactory\Training\PersonalBestDataModelFactory;
use App\Domain\Registry\Training\PersonalBestKindRegistry as Kind;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class PersonalBestDataModelFactoryTest extends TestCase
{
    private PersonalBestDataModelFactory $factory;
    private UserDataModel $owner;
    private int $day = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new PersonalBestDataModelFactory();
        $this->owner = new UserDataModel();
        $this->owner->id = 1;
    }

    public function testALoadedMovementHasItsLoadRecords(): void
    {
        $bench = $this->movement(reps: true, weight: true);
        $workout = $this->workout();
        $set = $this->set($bench, $workout, reps: 5, weight: 100.0);

        $rows = $this->factory->buildMovementProgression($this->owner, $bench, [$set]);

        self::assertSame(100.0, $this->current($rows, Kind::MAX_WEIGHT));
        // Five reps qualify for the 1, 3 and 5 rep tiers, not the 8.
        self::assertSame(100.0, $this->current($rows, Kind::MAX_WEIGHT_FOR_REPS, 1.0));
        self::assertSame(100.0, $this->current($rows, Kind::MAX_WEIGHT_FOR_REPS, 5.0));
        self::assertNull($this->current($rows, Kind::MAX_WEIGHT_FOR_REPS, 8.0));
        self::assertSame(116.67, $this->current($rows, Kind::ESTIMATED_ONE_REP_MAX));
        self::assertSame(500.0, $this->current($rows, Kind::MAX_SET_VOLUME));
        self::assertSame(500.0, $this->current($rows, Kind::MAX_WORKOUT_VOLUME));
        self::assertNull($this->current($rows, Kind::MAX_REPS));

        self::assertSame($set, $this->row($rows, Kind::MAX_WEIGHT)?->set);
        self::assertNull($this->row($rows, Kind::MAX_WORKOUT_VOLUME)?->set);
        self::assertSame($workout, $this->row($rows, Kind::MAX_WORKOUT_VOLUME)?->workout);
        self::assertSame($workout->startedAt, $this->row($rows, Kind::MAX_WEIGHT)?->achievedAt);
    }

    /** A single is its own load; past ESTIMATE_MAX_REPS reps, Epley is not trusted. */
    public function testTheEstimateReadsASingleAsItsLoadAndIgnoresLongSets(): void
    {
        $bench = $this->movement(reps: true, weight: true);
        $workout = $this->workout();

        $single = $this->factory->buildMovementProgression($this->owner, $bench, [$this->set($bench, $workout, reps: 1, weight: 120.0)]);
        $long = $this->factory->buildMovementProgression($this->owner, $bench, [$this->set($bench, $workout, reps: 15, weight: 60.0)]);

        self::assertSame(120.0, $this->current($single, Kind::ESTIMATED_ONE_REP_MAX));
        self::assertNull($this->current($long, Kind::ESTIMATED_ONE_REP_MAX));
    }

    /** A row each time a record goes up; a tie leaves it with the first. */
    public function testTheProgressionKeepsEachTimeARecordWentUp(): void
    {
        $bench = $this->movement(reps: true, weight: true);
        $first = $this->set($bench, $this->workout(), reps: 5, weight: 80.0);
        $tie = $this->set($bench, $this->workout(), reps: 5, weight: 80.0);
        $better = $this->set($bench, $this->workout(), reps: 5, weight: 90.0);

        $rows = $this->rows($this->factory->buildMovementProgression($this->owner, $bench, [$first, $tie, $better]), Kind::MAX_WEIGHT);

        self::assertSame([80.0, 90.0], array_map(static fn (PersonalBestDataModel $row): float => $row->value, $rows));
        self::assertSame([$first, $better], array_map(static fn (PersonalBestDataModel $row): ?WorkoutSetDataModel => $row->set, $rows));
    }

    /** The sets of one workout add up for the workout records, and only theirs. */
    public function testWorkoutRecordsAddUpTheSetsOfEachWorkout(): void
    {
        $pushUp = $this->movement(reps: true);
        $monday = $this->workout();
        $friday = $this->workout();
        $sets = [
            $this->set($pushUp, $monday, reps: 20),
            $this->set($pushUp, $monday, reps: 15),
            $this->set($pushUp, $friday, reps: 30),
        ];

        $rows = $this->factory->buildMovementProgression($this->owner, $pushUp, $sets);

        self::assertSame([35.0], array_map(static fn (PersonalBestDataModel $row): float => $row->value, $this->rows($rows, Kind::MAX_WORKOUT_REPS)));
        self::assertSame(30.0, $this->current($rows, Kind::MAX_REPS));
        self::assertNull($this->current($rows, Kind::MAX_WEIGHT));
    }

    public function testATimedMovementHasItsDurationRecords(): void
    {
        $plank = $this->movement(duration: true);
        $workout = $this->workout();

        $rows = $this->factory->buildMovementProgression($this->owner, $plank, [
            $this->set($plank, $workout, duration: 60),
            $this->set($plank, $workout, duration: 90),
        ]);

        self::assertSame(90.0, $this->current($rows, Kind::MAX_DURATION));
        self::assertSame(150.0, $this->current($rows, Kind::MAX_WORKOUT_DURATION));
    }

    /** 6 km in 30 min counts at the same pace for 1 km and 5 km — 5:00 and 25:00 — not for 10 km. */
    public function testARunIsBroughtBackToEachTierItReachesAtItsPace(): void
    {
        $run = $this->movement(duration: true, distance: true);
        $workout = $this->workout();

        $rows = $this->factory->buildMovementProgression($this->owner, $run, [$this->set($run, $workout, duration: 1800, distance: 6000)]);

        self::assertSame(300.0, $this->current($rows, Kind::BEST_TIME_FOR_DISTANCE, 1000.0));
        self::assertSame(1500.0, $this->current($rows, Kind::BEST_TIME_FOR_DISTANCE, 5000.0));
        self::assertNull($this->current($rows, Kind::BEST_TIME_FOR_DISTANCE, 10000.0));
        self::assertSame(300.0, $this->current($rows, Kind::BEST_PACE));
        self::assertSame(6000.0, $this->current($rows, Kind::MAX_DISTANCE));
        self::assertSame(6000.0, $this->current($rows, Kind::MAX_WORKOUT_DISTANCE));
        // A run's duration is a time, not a hold: no duration record.
        self::assertNull($this->current($rows, Kind::MAX_DURATION));
    }

    /** A lower time beats; a sprint under PACE_MIN_DISTANCE sets no pace. */
    public function testFasterBeatsAndASprintSetsNoPace(): void
    {
        $run = $this->movement(duration: true, distance: true);
        $slow = $this->set($run, $this->workout(), duration: 1800, distance: 5000);
        $fast = $this->set($run, $this->workout(), duration: 1500, distance: 5000);
        $sprint = $this->set($run, $this->workout(), duration: 15, distance: 100);

        $rows = $this->factory->buildMovementProgression($this->owner, $run, [$slow, $fast, $sprint]);

        self::assertSame([1800.0, 1500.0], array_map(static fn (PersonalBestDataModel $row): float => $row->value, $this->rows($rows, Kind::BEST_TIME_FOR_DISTANCE, 5000.0)));
        self::assertSame(300.0, $this->current($rows, Kind::BEST_PACE));
    }

    /** A carry counts its distance per load: the load is the tier. */
    public function testACarryKeepsItsFurthestDistanceForEachLoad(): void
    {
        $farmerWalk = $this->movement(weight: true, distance: true);
        $workout = $this->workout();

        $rows = $this->factory->buildMovementProgression($this->owner, $farmerWalk, [
            $this->set($farmerWalk, $workout, weight: 24.0, distance: 40),
            $this->set($farmerWalk, $workout, weight: 32.0, distance: 20),
            $this->set($farmerWalk, $workout, weight: 24.0, distance: 60),
        ]);

        self::assertSame(60.0, $this->current($rows, Kind::MAX_DISTANCE_FOR_WEIGHT, 24.0));
        self::assertSame(20.0, $this->current($rows, Kind::MAX_DISTANCE_FOR_WEIGHT, 32.0));
        self::assertSame(32.0, $this->current($rows, Kind::MAX_WEIGHT));
        self::assertNull($this->current($rows, Kind::MAX_DISTANCE));
    }

    /** An empty bar is a load, not a record. */
    public function testNothingOfNothingIsARecord(): void
    {
        $bench = $this->movement(reps: true, weight: true);

        $rows = $this->factory->buildMovementProgression($this->owner, $bench, [$this->set($bench, $this->workout(), reps: 10, weight: 0.0)]);

        self::assertNull($this->current($rows, Kind::MAX_WEIGHT));
        self::assertNull($this->current($rows, Kind::MAX_SET_VOLUME));
    }

    public function testWholeWorkoutsHaveTheirOwnRecords(): void
    {
        $short = $this->workout(finishedAfterMinutes: 45);
        $long = $this->workout(finishedAfterMinutes: 90);
        $running = $this->workout();

        $rows = $this->factory->buildSessionProgression($this->owner, [
            new WorkoutTally($short, 12, 3000.0),
            new WorkoutTally($long, 10, null),
            new WorkoutTally($running, 20, 2000.0),
        ]);

        self::assertSame(3000.0, $this->current($rows, Kind::MAX_SESSION_VOLUME));
        self::assertSame([12.0, 20.0], array_map(static fn (PersonalBestDataModel $row): float => $row->value, $this->rows($rows, Kind::MAX_SESSION_SETS)));
        // Only a finished workout has a length.
        self::assertSame(5400.0, $this->current($rows, Kind::LONGEST_SESSION));
        self::assertNull($this->row($rows, Kind::LONGEST_SESSION)?->movement);
    }

    /**
     * @param list<PersonalBestDataModel> $rows
     *
     * @return list<PersonalBestDataModel>
     */
    private function rows(array $rows, string $kind, ?float $tier = null): array
    {
        return array_values(array_filter($rows, static fn (PersonalBestDataModel $row): bool => $kind === $row->kind && $tier === $row->tier));
    }

    /** @param list<PersonalBestDataModel> $rows */
    private function row(array $rows, string $kind, ?float $tier = null): ?PersonalBestDataModel
    {
        $matching = $this->rows($rows, $kind, $tier);

        return [] === $matching ? null : $matching[count($matching) - 1];
    }

    /** @param list<PersonalBestDataModel> $rows */
    private function current(array $rows, string $kind, ?float $tier = null): ?float
    {
        return $this->row($rows, $kind, $tier)?->value;
    }

    private function movement(bool $reps = false, bool $weight = false, bool $duration = false, bool $distance = false): MovementDataModel
    {
        $movement = new MovementDataModel();
        $movement->tracksReps = $reps;
        $movement->tracksWeight = $weight;
        $movement->tracksDuration = $duration;
        $movement->tracksDistance = $distance;

        return $movement;
    }

    /** A day after the last one: the order sets are handed in is the order they were done. */
    private function workout(?int $finishedAfterMinutes = null): WorkoutDataModel
    {
        $workout = new WorkoutDataModel();
        $workout->owner = $this->owner;
        $workout->startedAt = new DateTimeImmutable(sprintf('2026-10-%02d 18:00:00', ++$this->day));
        $workout->finishedAt = null === $finishedAfterMinutes ? null : $workout->startedAt->modify("+{$finishedAfterMinutes} minutes");

        return $workout;
    }

    private function set(
        MovementDataModel $movement,
        WorkoutDataModel $workout,
        ?int $reps = null,
        ?float $weight = null,
        ?int $duration = null,
        ?int $distance = null,
    ): WorkoutSetDataModel {
        $block = new WorkoutBlockDataModel();
        $block->workout = $workout;
        $exercise = new WorkoutExerciseDataModel();
        $exercise->block = $block;
        $exercise->movement = $movement;

        $set = new WorkoutSetDataModel();
        $set->exercise = $exercise;
        $set->reps = $reps;
        $set->weightInKilograms = $weight;
        $set->durationInSeconds = $duration;
        $set->distanceInMetres = $distance;
        $set->isComplete = true;

        return $set;
    }
}
