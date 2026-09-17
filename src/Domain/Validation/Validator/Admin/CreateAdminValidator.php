<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Admin;

use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\DTO\Input\Admin\CreateAdminDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\User\EmailAvailableConstraint;
use App\Domain\Validation\Constraint\User\UsernameAvailableConstraint;
use App\Domain\Validation\Validator\AbstractBaseValidator;

/**
 * @extends AbstractBaseValidator<CreateAdminDataInput>
 */
final readonly class CreateAdminValidator extends AbstractBaseValidator
{
    public const string ERROR_CODE = 'create_admin_invalid';

    /**
     * @param UserDataModel|null $userWithSameEmail    the account already holding that e-mail, if any
     * @param UserDataModel|null $userWithSameUsername the account already holding that username, if any
     *
     * @throws ValidationException
     */
    public function validate(
        CreateAdminDataInput $input,
        ?UserDataModel $userWithSameEmail,
        ?UserDataModel $userWithSameUsername,
    ): void {
        $violations = $this->getViolations($input);
        $violations = EmailAvailableConstraint::validate($userWithSameEmail, $violations);
        $violations = UsernameAvailableConstraint::validate($userWithSameUsername, $violations);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
