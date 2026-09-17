<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Session;

use App\Domain\DTO\Input\Session\CreateSessionDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Validator\AbstractBaseValidator;

/**
 * Shape only. Whether the credentials actually match is not a violation: it answers 401, and it
 * must answer the same way for an unknown e-mail and for a wrong password.
 *
 * @extends AbstractBaseValidator<CreateSessionDataInput>
 */
final readonly class CreateSessionValidator extends AbstractBaseValidator
{
    public const string ERROR_CODE = 'create_session_invalid';

    /**
     * @throws ValidationException
     */
    public function validate(CreateSessionDataInput $input): void
    {
        $violations = $this->getViolations($input);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
