<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Training;

use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutSetDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Domain\Validation\Constraint\Training\WorkoutInProgressConstraint;
use App\Domain\Validation\Constraint\Training\WorkoutSetInWorkoutConstraint;

final readonly class CompleteWorkoutSetValidator
{
    public const string ERROR_CODE = 'complete_workout_set_invalid';

    /**
     * @throws ValidationException
     */
    public function validate(UserDataModel $currentUser, WorkoutDataModel $workout, WorkoutSetDataModel $set): void
    {
        $access = IsOwnerConstraint::validate($currentUser, $workout->owner);
        if ([] !== $access) {
            throw new ValidationException(self::ERROR_CODE, $access);
        }

        // Alone as well: nothing is said about what another workout holds.
        $part = WorkoutSetInWorkoutConstraint::validate($set, $workout);
        if ([] !== $part) {
            throw new ValidationException(self::ERROR_CODE, $part);
        }

        $violations = [];
        $violations = WorkoutInProgressConstraint::validate($workout, $violations);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
