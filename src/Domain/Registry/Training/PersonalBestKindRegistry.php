<?php

declare(strict_types=1);

namespace App\Domain\Registry\Training;

/**
 * The kinds of personal best. Which ones a movement has follows from the measures it tracks —
 * PersonalBestDataModelFactory reads them — and the API answers the code; each front end words it.
 */
interface PersonalBestKindRegistry
{
    // Beaten by one set of a movement.
    public const string MAX_WEIGHT = 'max_weight';
    /** Tiered by a number of reps: the heaviest load lifted for at least that many. */
    public const string MAX_WEIGHT_FOR_REPS = 'max_weight_for_reps';
    /** Epley's estimate, over sets of ESTIMATE_MAX_REPS reps at most: beyond, it drifts. */
    public const string ESTIMATED_ONE_REP_MAX = 'estimated_one_rep_max';
    public const string MAX_SET_VOLUME = 'max_set_volume';
    public const string MAX_REPS = 'max_reps';
    public const string MAX_DURATION = 'max_duration';
    public const string MAX_DISTANCE = 'max_distance';
    /** Tiered by a distance: a set at least that long, its time brought back to it at its pace. */
    public const string BEST_TIME_FOR_DISTANCE = 'best_time_for_distance';
    /** Seconds per kilometre, over sets of PACE_MIN_DISTANCE at least: a sprint would win it otherwise. */
    public const string BEST_PACE = 'best_pace';
    /** Tiered by the load itself: the furthest it was carried. */
    public const string MAX_DISTANCE_FOR_WEIGHT = 'max_distance_for_weight';

    // Beaten by one workout's sets of a movement, added up.
    public const string MAX_WORKOUT_VOLUME = 'max_workout_volume';
    public const string MAX_WORKOUT_REPS = 'max_workout_reps';
    public const string MAX_WORKOUT_DURATION = 'max_workout_duration';
    public const string MAX_WORKOUT_DISTANCE = 'max_workout_distance';

    // Beaten by a whole workout, across movements.
    public const string MAX_SESSION_VOLUME = 'max_session_volume';
    public const string MAX_SESSION_SETS = 'max_session_sets';
    /** From start to finish: only a finished workout has one. */
    public const string LONGEST_SESSION = 'longest_session';

    /** The kinds a lower value beats; every other kind is beaten by a higher one. */
    public const array LOWER_IS_BETTER = [
        self::BEST_TIME_FOR_DISTANCE,
        self::BEST_PACE,
    ];

    /** The order records are listed in. */
    public const array ALL = [
        self::MAX_WEIGHT,
        self::MAX_WEIGHT_FOR_REPS,
        self::ESTIMATED_ONE_REP_MAX,
        self::MAX_SET_VOLUME,
        self::MAX_WORKOUT_VOLUME,
        self::MAX_REPS,
        self::MAX_WORKOUT_REPS,
        self::MAX_DURATION,
        self::MAX_WORKOUT_DURATION,
        self::MAX_DISTANCE,
        self::MAX_WORKOUT_DISTANCE,
        self::BEST_TIME_FOR_DISTANCE,
        self::BEST_PACE,
        self::MAX_DISTANCE_FOR_WEIGHT,
        self::MAX_SESSION_VOLUME,
        self::MAX_SESSION_SETS,
        self::LONGEST_SESSION,
    ];

    public const int ESTIMATE_MAX_REPS = 10;
    public const int PACE_MIN_DISTANCE = 1000;
}
