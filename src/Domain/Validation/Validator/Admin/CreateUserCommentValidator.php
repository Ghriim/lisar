<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Admin;

use App\Domain\DTO\Input\Admin\CreateUserCommentDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Validator\AbstractBaseValidator;

/**
 * @extends AbstractBaseValidator<CreateUserCommentDataInput>
 */
final readonly class CreateUserCommentValidator extends AbstractBaseValidator
{
    public const string ERROR_CODE = 'create_user_comment_invalid';

    /**
     * @throws ValidationException
     */
    public function validate(CreateUserCommentDataInput $input): void
    {
        $violations = $this->getViolations($input);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
