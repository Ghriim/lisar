<?php

declare(strict_types=1);

namespace App\Domain\Factory\DataModelFactory\Training;

use App\Domain\DTO\Aggregate\Training\WorkoutTally;
use App\Domain\DTO\DataModel\Training\MovementDataModel;
use App\Domain\DTO\DataModel\Training\PersonalBestDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutSetDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Registry\Training\PersonalBestKindRegistry;
use App\Domain\Registry\Training\PersonalBestTierRegistry;

use function in_array;
use function round;

/**
 * Builds the progression of personal bests from what was done: walking it oldest first, a row
 * each time a value strictly beats the best so far. A tie leaves the record with whoever set it
 * first. Nothing here reads or writes: it is handed the sets, and it hands rows back.
 *
 * Which kinds a movement has follows from the measures it tracks. A unilateral movement is read
 * like any other: its reps and loads as logged.
 */
final readonly class PersonalBestDataModelFactory
{
    /**
     * @param list<WorkoutSetDataModel> $sets the owner's sets of that movement that count for
     *                                        personal bests, oldest first
     *
     * @return list<PersonalBestDataModel>
     */
    public function buildMovementProgression(UserDataModel $owner, MovementDataModel $movement, array $sets): array
    {
        /** @var array<string, float> $best */
        $best = [];
        $rows = [];

        $workout = null;
        $workoutSets = [];
        foreach ($sets as $set) {
            $setWorkout = $set->exercise->block->workout;
            if (null !== $workout && $setWorkout !== $workout) {
                $this->offerWorkout($best, $rows, $owner, $movement, $workout, $workoutSets);
                $workoutSets = [];
            }
            $workout = $setWorkout;
            $workoutSets[] = $set;

            foreach ($this->setCandidates($movement, $set) as [$kind, $tier, $value]) {
                $this->offer($best, $rows, $owner, $movement, $kind, $tier, $value, $setWorkout, $set);
            }
        }

        if (null !== $workout) {
            $this->offerWorkout($best, $rows, $owner, $movement, $workout, $workoutSets);
        }

        return $rows;
    }

    /**
     * @param list<WorkoutTally> $tallies the owner's workouts, oldest first
     *
     * @return list<PersonalBestDataModel>
     */
    public function buildSessionProgression(UserDataModel $owner, array $tallies): array
    {
        /** @var array<string, float> $best */
        $best = [];
        $rows = [];

        foreach ($tallies as $tally) {
            $workout = $tally->workout;

            if (null !== $tally->volumeInKilograms) {
                $this->offer($best, $rows, $owner, null, PersonalBestKindRegistry::MAX_SESSION_VOLUME, null, $tally->volumeInKilograms, $workout, null);
            }
            $this->offer($best, $rows, $owner, null, PersonalBestKindRegistry::MAX_SESSION_SETS, null, $tally->setCount, $workout, null);

            if (null !== $workout->finishedAt) {
                $seconds = $workout->finishedAt->getTimestamp() - $workout->startedAt->getTimestamp();
                $this->offer($best, $rows, $owner, null, PersonalBestKindRegistry::LONGEST_SESSION, null, $seconds, $workout, null);
            }
        }

        return $rows;
    }

    /**
     * What one set puts forward, kind by kind.
     *
     * @return list<array{string, float|null, float}>
     */
    private function setCandidates(MovementDataModel $movement, WorkoutSetDataModel $set): array
    {
        $reps = $set->reps;
        $weight = $set->weightInKilograms;
        $duration = $set->durationInSeconds;
        $distance = $set->distanceInMetres;
        $candidates = [];

        if (true === $movement->tracksWeight && null !== $weight) {
            $candidates[] = [PersonalBestKindRegistry::MAX_WEIGHT, null, $weight];
        }

        if (true === $movement->tracksReps && true === $movement->tracksWeight && null !== $reps && null !== $weight) {
            foreach (PersonalBestTierRegistry::REPS as $tier) {
                if ($reps >= $tier) {
                    $candidates[] = [PersonalBestKindRegistry::MAX_WEIGHT_FOR_REPS, (float) $tier, $weight];
                }
            }
            if ($reps <= PersonalBestKindRegistry::ESTIMATE_MAX_REPS) {
                // Epley's formula, which reads a single rep as more than its own load: a single is the load.
                $estimate = 1 === $reps ? $weight : $weight * (1 + $reps / 30);
                $candidates[] = [PersonalBestKindRegistry::ESTIMATED_ONE_REP_MAX, null, $estimate];
            }
            $candidates[] = [PersonalBestKindRegistry::MAX_SET_VOLUME, null, $reps * $weight];
        }

        if (true === $movement->tracksReps && false === $movement->tracksWeight && null !== $reps) {
            $candidates[] = [PersonalBestKindRegistry::MAX_REPS, null, $reps];
        }

        if (true === $movement->tracksDuration && false === $movement->tracksDistance && null !== $duration) {
            $candidates[] = [PersonalBestKindRegistry::MAX_DURATION, null, $duration];
        }

        if (true === $movement->tracksDistance && false === $movement->tracksWeight && null !== $distance) {
            $candidates[] = [PersonalBestKindRegistry::MAX_DISTANCE, null, $distance];
        }

        if (true === $movement->tracksDistance && true === $movement->tracksDuration && false === $movement->tracksWeight
            && null !== $distance && null !== $duration && 0 < $distance) {
            foreach (PersonalBestTierRegistry::DISTANCES_IN_METRES as $tier) {
                if ($distance >= $tier) {
                    // Brought back to the tier at the set's own pace.
                    $candidates[] = [PersonalBestKindRegistry::BEST_TIME_FOR_DISTANCE, (float) $tier, $duration * $tier / $distance];
                }
            }
            if ($distance >= PersonalBestKindRegistry::PACE_MIN_DISTANCE) {
                $candidates[] = [PersonalBestKindRegistry::BEST_PACE, null, $duration / $distance * 1000];
            }
        }

        if (true === $movement->tracksWeight && true === $movement->tracksDistance && null !== $weight && null !== $distance) {
            $candidates[] = [PersonalBestKindRegistry::MAX_DISTANCE_FOR_WEIGHT, $weight, $distance];
        }

        return $candidates;
    }

    /**
     * What one workout's sets of the movement put forward, added up.
     *
     * @param array<string, float>        $best
     * @param list<PersonalBestDataModel> $rows
     * @param list<WorkoutSetDataModel>   $sets
     */
    private function offerWorkout(array &$best, array &$rows, UserDataModel $owner, MovementDataModel $movement, WorkoutDataModel $workout, array $sets): void
    {
        $volume = null;
        $reps = null;
        $duration = null;
        $distance = null;

        foreach ($sets as $set) {
            if (null !== $set->reps && null !== $set->weightInKilograms) {
                $volume = ($volume ?? 0.0) + $set->reps * $set->weightInKilograms;
            }
            if (null !== $set->reps) {
                $reps = ($reps ?? 0) + $set->reps;
            }
            if (null !== $set->durationInSeconds) {
                $duration = ($duration ?? 0) + $set->durationInSeconds;
            }
            if (null !== $set->distanceInMetres) {
                $distance = ($distance ?? 0) + $set->distanceInMetres;
            }
        }

        $totals = [];
        if (true === $movement->tracksReps && true === $movement->tracksWeight && null !== $volume) {
            $totals[PersonalBestKindRegistry::MAX_WORKOUT_VOLUME] = $volume;
        }
        if (true === $movement->tracksReps && false === $movement->tracksWeight && null !== $reps) {
            $totals[PersonalBestKindRegistry::MAX_WORKOUT_REPS] = $reps;
        }
        if (true === $movement->tracksDuration && false === $movement->tracksDistance && null !== $duration) {
            $totals[PersonalBestKindRegistry::MAX_WORKOUT_DURATION] = $duration;
        }
        if (true === $movement->tracksDistance && false === $movement->tracksWeight && null !== $distance) {
            $totals[PersonalBestKindRegistry::MAX_WORKOUT_DISTANCE] = $distance;
        }

        foreach ($totals as $kind => $value) {
            $this->offer($best, $rows, $owner, $movement, $kind, null, $value, $workout, null);
        }
    }

    /**
     * Adds a row when `$value` strictly beats the best so far for that kind and tier. Nothing of
     * nothing is a record: a zero load, an empty workout.
     *
     * @param array<string, float>        $best
     * @param list<PersonalBestDataModel> $rows
     */
    private function offer(
        array &$best,
        array &$rows,
        UserDataModel $owner,
        ?MovementDataModel $movement,
        string $kind,
        ?float $tier,
        int|float $value,
        WorkoutDataModel $workout,
        ?WorkoutSetDataModel $set,
    ): void {
        // Compared as stored: two values the column cannot tell apart are a tie.
        $value = round((float) $value, 2);
        if (0.0 >= $value) {
            return;
        }

        $key = $kind.'|'.(null === $tier ? '' : (string) $tier);
        $isLowerBetter = in_array($kind, PersonalBestKindRegistry::LOWER_IS_BETTER, true);
        if (true === isset($best[$key])) {
            $beats = true === $isLowerBetter ? $value < $best[$key] : $value > $best[$key];
            if (false === $beats) {
                return;
            }
        }
        $best[$key] = $value;

        $row = new PersonalBestDataModel();
        $row->owner = $owner;
        $row->movement = $movement;
        $row->kind = $kind;
        $row->tier = $tier;
        $row->value = $value;
        $row->workout = $workout;
        $row->set = $set;
        $row->achievedAt = $workout->startedAt;
        $rows[] = $row;
    }
}
