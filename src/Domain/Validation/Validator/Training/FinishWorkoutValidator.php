<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Training;

use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Domain\Validation\Constraint\Training\WorkoutFinishableConstraint;

final readonly class FinishWorkoutValidator
{
    public const string ERROR_CODE = 'finish_workout_invalid';

    /**
     * @throws ValidationException
     */
    public function validate(UserDataModel $currentUser, WorkoutDataModel $workout): void
    {
        $access = IsOwnerConstraint::validate($currentUser, $workout->owner);
        if ([] !== $access) {
            throw new ValidationException(self::ERROR_CODE, $access);
        }

        $violations = [];
        $violations = WorkoutFinishableConstraint::validate($workout, $violations);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
