<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Todo\Admin;

use App\Domain\DTO\DataModel\Todo\PriorityDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsAdminConstraint;
use App\Domain\Validation\Constraint\Todo\PriorityDeletableConstraint;

/**
 * `$taskCount` is how many tasks, anyone's, carry the priority.
 */
final readonly class DeletePriorityValidator
{
    public const string ERROR_CODE = 'delete_priority_invalid';

    /**
     * @throws ValidationException
     */
    public function validate(UserDataModel $currentUser, PriorityDataModel $priority, int $taskCount): void
    {
        $access = IsAdminConstraint::validate($currentUser);
        if ([] !== $access) {
            throw new ValidationException(self::ERROR_CODE, $access);
        }

        $violations = [];
        $violations = PriorityDeletableConstraint::validate($priority, $taskCount, $violations);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
