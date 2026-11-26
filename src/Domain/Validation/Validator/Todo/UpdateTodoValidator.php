<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Todo;

use App\Domain\DTO\DataModel\Todo\TodoDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Todo\SaveTodoDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Domain\Validation\Constraint\Todo\TodoActiveConstraint;
use App\Domain\Validation\Constraint\Todo\TodoTitleAvailableConstraint;
use App\Domain\Validation\Validator\AbstractBaseValidator;

/**
 * Renaming: the default may be renamed, an archived TODO may not.
 *
 * @extends AbstractBaseValidator<SaveTodoDataInput>
 */
final readonly class UpdateTodoValidator extends AbstractBaseValidator
{
    public const string ERROR_CODE = 'update_todo_invalid';

    /**
     * @param TodoDataModel|null $sameTitle the account's TODO already by that title, if any
     *
     * @throws ValidationException
     */
    public function validate(UserDataModel $currentUser, TodoDataModel $todo, SaveTodoDataInput $input, ?TodoDataModel $sameTitle): void
    {
        $access = IsOwnerConstraint::validate($currentUser, $todo->owner);
        if ([] !== $access) {
            throw new ValidationException(self::ERROR_CODE, $access);
        }

        $violations = $this->getViolations($input);
        $violations = TodoActiveConstraint::validate($todo, 'id', $violations);
        $violations = TodoTitleAvailableConstraint::validate($sameTitle, $todo->id, $violations);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
