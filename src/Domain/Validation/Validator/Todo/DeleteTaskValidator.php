<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Todo;

use App\Domain\DTO\DataModel\Todo\TaskDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;

/**
 * What `DeleteTaskUseCase` checks before it acts: that the task is the caller's.
 */
final readonly class DeleteTaskValidator
{
    public const string ERROR_CODE = 'delete_task_invalid';

    /**
     * @throws ValidationException
     */
    public function validate(UserDataModel $currentUser, TaskDataModel $task): void
    {
        $access = IsOwnerConstraint::validate($currentUser, $task->owner);
        if ([] !== $access) {
            throw new ValidationException(self::ERROR_CODE, $access);
        }
    }
}
