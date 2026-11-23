<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Training;

use App\Domain\DTO\DataModel\Training\RoutineDataModel;
use App\Domain\DTO\DataModel\Training\RoutineExerciseDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Domain\Validation\Constraint\Training\RoutineExerciseInRoutineConstraint;

/** What `DeleteRoutineExerciseUseCase` checks before it acts: that the routine is the caller's, and the movement one of its own. */
final readonly class DeleteRoutineExerciseValidator
{
    public const string ERROR_CODE = 'delete_routine_exercise_invalid';

    /**
     * @throws ValidationException
     */
    public function validate(UserDataModel $currentUser, RoutineDataModel $routine, RoutineExerciseDataModel $exercise): void
    {
        $access = IsOwnerConstraint::validate($currentUser, $routine->owner);
        if ([] !== $access) {
            throw new ValidationException(self::ERROR_CODE, $access);
        }

        // Alone as well: nothing is said about what another routine holds.
        $part = RoutineExerciseInRoutineConstraint::validate($exercise, $routine);
        if ([] !== $part) {
            throw new ValidationException(self::ERROR_CODE, $part);
        }
    }
}
