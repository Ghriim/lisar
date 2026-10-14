<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Todo;

use App\Domain\DTO\DataModel\Todo\CategoryDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Todo\CategoryEditableConstraint;
use App\Domain\Validation\Constraint\Todo\CategoryUnusedConstraint;

/**
 * `$taskCount` is how many tasks sit in the category.
 */
final readonly class DeleteCategoryValidator
{
    public const string ERROR_CODE = 'delete_category_invalid';

    /**
     * @throws ValidationException
     */
    public function validate(UserDataModel $currentUser, CategoryDataModel $category, int $taskCount): void
    {
        $violations = [];
        $violations = CategoryEditableConstraint::validate($category, $violations);
        $violations = CategoryUnusedConstraint::validate($taskCount, $violations);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
