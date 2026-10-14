<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Training;

use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;

/**
 * What `DeleteWorkoutBlockUseCase` checks before it acts: that the workout is the caller's.
 */
final readonly class DeleteWorkoutBlockValidator
{
    public const string ERROR_CODE = 'delete_workout_block_invalid';

    /**
     * @throws ValidationException
     */
    public function validate(UserDataModel $currentUser, WorkoutDataModel $workout): void
    {
        $access = IsOwnerConstraint::validate($currentUser, $workout->owner);
        if ([] !== $access) {
            throw new ValidationException(self::ERROR_CODE, $access);
        }
    }
}
