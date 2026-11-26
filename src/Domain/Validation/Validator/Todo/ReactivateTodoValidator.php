<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Todo;

use App\Domain\DTO\DataModel\Todo\TodoDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;

/**
 * Making an archived TODO active again: the caller's own is all it takes. The next rule lands
 * here, as a Constraint.
 */
final readonly class ReactivateTodoValidator
{
    public const string ERROR_CODE = 'reactivate_todo_invalid';

    /**
     * @throws ValidationException
     */
    public function validate(UserDataModel $currentUser, TodoDataModel $todo): void
    {
        $access = IsOwnerConstraint::validate($currentUser, $todo->owner);
        if ([] !== $access) {
            throw new ValidationException(self::ERROR_CODE, $access);
        }
    }
}
