<?php

declare(strict_types=1);

namespace App\Domain\Validation\Constraint\Training;

use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutSetDataModel;

/**
 * The set named in the route is one of the workout named with it. One of another workout — someone
 * else's, or another of the caller's — is answered like one that does not exist: its id says
 * nothing.
 */
final readonly class WorkoutSetInWorkoutConstraint
{
    public const string NOT_FOUND = 'workout_set_not_found';

    /**
     * @param array<string, list<string>> $violations
     *
     * @return array<string, list<string>>
     */
    public static function validate(WorkoutSetDataModel $set, WorkoutDataModel $workout, array $violations = []): array
    {
        // The same row is the same instance within a request: the identity map sees to it.
        if ($workout !== $set->exercise->block->workout) {
            $violations['setId'][] = self::NOT_FOUND;
        }

        return $violations;
    }
}
