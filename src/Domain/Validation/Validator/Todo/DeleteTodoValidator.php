<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Todo;

use App\Domain\DTO\DataModel\Todo\TodoDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Domain\Validation\Constraint\Todo\TodoNotDefaultConstraint;

/**
 * Deleting a TODO, and its tasks with it: never the default.
 */
final readonly class DeleteTodoValidator
{
    public const string ERROR_CODE = 'delete_todo_invalid';

    /**
     * @throws ValidationException
     */
    public function validate(UserDataModel $currentUser, TodoDataModel $todo): void
    {
        $access = IsOwnerConstraint::validate($currentUser, $todo->owner);
        if ([] !== $access) {
            throw new ValidationException(self::ERROR_CODE, $access);
        }

        $violations = [];
        $violations = TodoNotDefaultConstraint::validate($todo, $violations);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
