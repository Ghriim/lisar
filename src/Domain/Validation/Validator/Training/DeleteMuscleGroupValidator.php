<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Training;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsAdminConstraint;
use App\Domain\Validation\Constraint\Training\MuscleGroupUnusedConstraint;

/**
 * `$muscleCount` is how many muscles sit in the group.
 */
final readonly class DeleteMuscleGroupValidator
{
    public const string ERROR_CODE = 'delete_muscle_group_invalid';

    /**
     * @throws ValidationException
     */
    public function validate(UserDataModel $currentUser, int $muscleCount): void
    {
        $access = IsAdminConstraint::validate($currentUser);
        if ([] !== $access) {
            throw new ValidationException(self::ERROR_CODE, $access);
        }

        $violations = [];
        $violations = MuscleGroupUnusedConstraint::validate($muscleCount, $violations);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
