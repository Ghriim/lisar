<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Training;

use App\Domain\DTO\DataModel\Training\SetTypeDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsAdminConstraint;
use App\Domain\Validation\Constraint\Training\SetTypeNotDefaultConstraint;

final readonly class DeactivateSetTypeValidator
{
    public const string ERROR_CODE = 'deactivate_set_type_invalid';

    /**
     * @throws ValidationException
     */
    public function validate(UserDataModel $currentUser, SetTypeDataModel $setType): void
    {
        $access = IsAdminConstraint::validate($currentUser);
        if ([] !== $access) {
            throw new ValidationException(self::ERROR_CODE, $access);
        }

        $violations = [];
        $violations = SetTypeNotDefaultConstraint::validate($setType, $violations);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
