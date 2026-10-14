<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\User\Admin;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\User\Admin\CreateUserCommentDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsAdminConstraint;
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
    public function validate(UserDataModel $currentUser, CreateUserCommentDataInput $input): void
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
