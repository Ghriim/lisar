<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Training;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsAdminConstraint;

/**
 * What `ListMuscleGroupsForAdminUseCase` checks before it acts: that the caller is an administrator.
 */
final readonly class ListMuscleGroupsForAdminValidator
{
    public const string ERROR_CODE = 'list_muscle_groups_for_admin_invalid';

    /**
     * @throws ValidationException
     */
    public function validate(UserDataModel $currentUser): void
    {
        $access = IsAdminConstraint::validate($currentUser);
        if ([] !== $access) {
            throw new ValidationException(self::ERROR_CODE, $access);
        }
    }
}
