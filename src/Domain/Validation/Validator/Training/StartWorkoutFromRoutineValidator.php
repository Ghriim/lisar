<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Training;

use App\Domain\DTO\DataModel\Training\RoutineDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Domain\Validation\Constraint\Training\RoutineGivesSetsConstraint;
use App\Domain\Validation\Constraint\Training\WorkoutNotInProgressConstraint;

/**
 * `$inProgress` is the owner's workout in progress, if any; `$setCount` how many sets the workout
 * the routine starts would hold, once what is no longer offered is left out.
 */
final readonly class StartWorkoutFromRoutineValidator
{
    public const string ERROR_CODE = 'start_workout_from_routine_invalid';

    /**
     * @throws ValidationException
     */
    public function validate(UserDataModel $currentUser, RoutineDataModel $routine, ?WorkoutDataModel $inProgress, int $setCount): void
    {
        $access = IsOwnerConstraint::validate($currentUser, $routine->owner);
        if ([] !== $access) {
            throw new ValidationException(self::ERROR_CODE, $access);
        }

        $violations = [];
        $violations = WorkoutNotInProgressConstraint::validate($inProgress, $violations);
        $violations = RoutineGivesSetsConstraint::validate($setCount, $violations);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
