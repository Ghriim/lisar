<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Training;

use App\Domain\DTO\DataModel\Training\SetTypeDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsAdminConstraint;
use App\Domain\Validation\Constraint\Training\SetTypeNotDefaultConstraint;
use App\Domain\Validation\Constraint\Training\SetTypeUnusedConstraint;

/**
 * `$setCount` is how many logged sets, anyone's, carry the type.
 */
final readonly class DeleteSetTypeValidator
{
    public const string ERROR_CODE = 'delete_set_type_invalid';

    /**
     * @throws ValidationException
     */
    public function validate(UserDataModel $currentUser, SetTypeDataModel $setType, int $setCount): void
    {
        $access = IsAdminConstraint::validate($currentUser);
        if ([] !== $access) {
            throw new ValidationException(self::ERROR_CODE, $access);
        }

        $violations = [];
        $violations = SetTypeUnusedConstraint::validate($setCount, $violations);
        $violations = SetTypeNotDefaultConstraint::validate($setType, $violations);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
