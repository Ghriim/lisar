<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Todo\Admin;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsAdminConstraint;
use App\Domain\Validation\Constraint\Todo\CategoryUnusedConstraint;

/**
 * `$taskCount` is how many tasks, across every account, sit in the category.
 */
final readonly class DeleteReferenceCategoryValidator
{
    public const string ERROR_CODE = 'delete_reference_category_invalid';

    /**
     * @throws ValidationException
     */
    public function validate(UserDataModel $currentUser, int $taskCount): void
    {
        $access = IsAdminConstraint::validate($currentUser);
        if ([] !== $access) {
            throw new ValidationException(self::ERROR_CODE, $access);
        }

        $violations = [];
        $violations = CategoryUnusedConstraint::validate($taskCount, $violations);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
