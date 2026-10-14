<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Training;

use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Domain\Validation\Constraint\Training\WorkoutNotInProgressConstraint;

/**
 * `$inProgress` is the owner's workout in progress, if any.
 */
final readonly class CopyWorkoutValidator
{
    public const string ERROR_CODE = 'copy_workout_invalid';

    /**
     * @throws ValidationException
     */
    public function validate(UserDataModel $currentUser, WorkoutDataModel $source, ?WorkoutDataModel $inProgress): void
    {
        $access = IsOwnerConstraint::validate($currentUser, $source->owner);
        if ([] !== $access) {
            throw new ValidationException(self::ERROR_CODE, $access);
        }

        $violations = [];
        $violations = WorkoutNotInProgressConstraint::validate($inProgress, $violations);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
