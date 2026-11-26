<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Todo;

use App\Domain\DTO\DataModel\Todo\TodoDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Todo\SaveTodoDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Todo\TodoTitleAvailableConstraint;
use App\Domain\Validation\Validator\AbstractBaseValidator;

/**
 * @extends AbstractBaseValidator<SaveTodoDataInput>
 */
final readonly class CreateTodoValidator extends AbstractBaseValidator
{
    public const string ERROR_CODE = 'create_todo_invalid';

    /**
     * @param TodoDataModel|null $sameTitle the account's TODO already by that title, if any
     *
     * @throws ValidationException
     */
    public function validate(UserDataModel $currentUser, SaveTodoDataInput $input, ?TodoDataModel $sameTitle): void
    {
        $violations = $this->getViolations($input);
        $violations = TodoTitleAvailableConstraint::validate($sameTitle, null, $violations);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
