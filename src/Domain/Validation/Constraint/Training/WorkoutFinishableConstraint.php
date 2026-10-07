<?php

declare(strict_types=1);

namespace App\Domain\Validation\Constraint\Training;

use App\Domain\DTO\DataModel\Training\WorkoutDataModel;

/**
 * A workout is finished once something was done in it, and only once all of it was: a workout
 * without a single set is one that did not happen — abandoning it is the way out — and a set not
 * ticked is one still to do, or to remove.
 */
final readonly class WorkoutFinishableConstraint
{
    public const string EMPTY = 'workout_empty';
    public const string INCOMPLETE_SETS = 'workout_has_incomplete_sets';

    /**
     * @param array<string, list<string>> $violations
     *
     * @return array<string, list<string>>
     */
    public static function validate(WorkoutDataModel $workout, array $violations = []): array
    {
        if (0 === $workout->countSets()) {
            $violations['workout'][] = self::EMPTY;
        }

        if (0 < $workout->countIncompleteSets()) {
            $violations['workout'][] = self::INCOMPLETE_SETS;
        }

        return $violations;
    }
}
