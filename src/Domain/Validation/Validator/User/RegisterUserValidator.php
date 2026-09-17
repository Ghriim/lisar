<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\User;

use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\DTO\Input\User\RegisterUserDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\User\EmailAvailableConstraint;
use App\Domain\Validation\Constraint\User\UsernameAvailableConstraint;
use App\Domain\Validation\Validator\AbstractBaseValidator;

/**
 * @extends AbstractBaseValidator<RegisterUserDataInput>
 */
final readonly class RegisterUserValidator extends AbstractBaseValidator
{
    public const string ERROR_CODE = 'register_user_invalid';

    /**
     * @param UserDataModel|null $userWithSameEmail    the account already holding that e-mail, if any
     * @param UserDataModel|null $userWithSameUsername the account already holding that username, if any
     *
     * @throws ValidationException
     */
    public function validate(
        RegisterUserDataInput $input,
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
