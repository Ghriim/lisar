<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Todo;

use App\Domain\DTO\DataModel\Todo\TaskDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Domain\Validation\Constraint\Todo\TaskFavoritableConstraint;
use App\Domain\Validation\Constraint\Todo\TodoActiveConstraint;

/** Starring a task: a root one, in an active TODO. */
final readonly class FavoriteTaskValidator
{
    public const string ERROR_CODE = 'favorite_task_invalid';

    /**
     * @throws ValidationException
     */
    public function validate(UserDataModel $currentUser, TaskDataModel $task): void
    {
        $access = IsOwnerConstraint::validate($currentUser, $task->owner);
        if ([] !== $access) {
            throw new ValidationException(self::ERROR_CODE, $access);
        }

        $violations = [];
        $violations = TaskFavoritableConstraint::validate($task, $violations);
        $violations = TodoActiveConstraint::validate($task->todo, 'id', $violations);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
