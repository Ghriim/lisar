<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Todo;

use App\Domain\DTO\DataModel\Todo\TodoDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Domain\Validation\Constraint\Todo\TodoNotDefaultConstraint;

/**
 * Putting a TODO away: never the default, which takes the tasks no TODO was named for.
 */
final readonly class ArchiveTodoValidator
{
    public const string ERROR_CODE = 'archive_todo_invalid';

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
