<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\User\Admin;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsAdminConstraint;

/**
 * What `ActivateUserUseCase` checks before it acts: that the caller is an administrator.
 */
final readonly class ActivateUserValidator
{
    public const string ERROR_CODE = 'activate_user_invalid';

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
