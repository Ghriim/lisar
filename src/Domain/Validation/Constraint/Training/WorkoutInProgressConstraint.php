<?php

declare(strict_types=1);

namespace App\Domain\Validation\Constraint\Training;

use App\Domain\DTO\DataModel\Training\WorkoutDataModel;

/**
 * What only makes sense while a workout runs — ticking a set as done. Once finished, every set in
 * it was done: there is nothing left to tick, and nothing to untick.
 */
final readonly class WorkoutInProgressConstraint
{
    public const string FINISHED = 'workout_finished';

    /**
     * @param array<string, list<string>> $violations
     *
     * @return array<string, list<string>>
     */
    public static function validate(WorkoutDataModel $workout, array $violations = []): array
    {
        if (false === $workout->isInProgress()) {
            $violations['workout'][] = self::FINISHED;
        }

        return $violations;
    }
}
