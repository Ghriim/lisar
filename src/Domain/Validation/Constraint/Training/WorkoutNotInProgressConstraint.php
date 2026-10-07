<?php

declare(strict_types=1);

namespace App\Domain\Validation\Constraint\Training;

use App\Domain\DTO\DataModel\Training\WorkoutDataModel;

/**
 * One workout in progress at a time: a second would split one session's sets across two, and
 * neither would say what was done.
 */
final readonly class WorkoutNotInProgressConstraint
{
    public const string ALREADY_IN_PROGRESS = 'workout_already_in_progress';

    /**
     * @param WorkoutDataModel|null       $inProgress the owner's workout in progress, if any
     * @param array<string, list<string>> $violations
     *
     * @return array<string, list<string>>
     */
    public static function validate(?WorkoutDataModel $inProgress, array $violations = []): array
    {
        if (null !== $inProgress) {
            $violations['workout'][] = self::ALREADY_IN_PROGRESS;
        }

        return $violations;
    }
}
