<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Admin;

use App\Domain\DTO\Input\Admin\ListUsersDataInput;
use App\Domain\Exception\ValidationException;
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
    public function validate(ListUsersDataInput $input): void
    {
        $violations = $this->getViolations($input);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
