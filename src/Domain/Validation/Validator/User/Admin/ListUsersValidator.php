<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\User\Admin;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\User\Admin\ListUsersDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsAdminConstraint;
use App\Domain\Validation\Validator\AbstractBaseValidator;

/**
 * @extends AbstractBaseValidator<ListUsersDataInput>
 */
final readonly class ListUsersValidator extends AbstractBaseValidator
{
    public const string ERROR_CODE = 'list_users_invalid';

    /**
     * @throws ValidationException
     */
    public function validate(UserDataModel $currentUser, ListUsersDataInput $input): void
    {
        $access = IsAdminConstraint::validate($currentUser);
        if ([] !== $access) {
            throw new ValidationException(self::ERROR_CODE, $access);
        }

        $violations = $this->getViolations($input);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
