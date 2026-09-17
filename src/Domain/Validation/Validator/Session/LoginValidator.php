<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Session;

use App\Domain\DTO\Input\Session\LoginDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Validator\AbstractBaseValidator;

/**
 * Shape only. Whether the credentials actually match is not a violation: it answers 401, and it
 * must answer the same way for an unknown e-mail and for a wrong password.
 *
 * @extends AbstractBaseValidator<LoginDataInput>
 */
final readonly class LoginValidator extends AbstractBaseValidator
{
    public const string ERROR_CODE = 'login_invalid';

    /**
     * @throws ValidationException
     */
    public function validate(LoginDataInput $input): void
    {
        $violations = $this->getViolations($input);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
