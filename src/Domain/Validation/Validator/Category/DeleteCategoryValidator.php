<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Category;

use App\Domain\DTO\DataModel\Category\CategoryDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Category\CategoryEditableConstraint;
use App\Domain\Validation\Constraint\Category\CategoryUnusedConstraint;

/**
 * `$useCount` is how many things — every domain together — sit in the category.
 */
final readonly class DeleteCategoryValidator
{
    public const string ERROR_CODE = 'delete_category_invalid';

    /**
     * @throws ValidationException
     */
    public function validate(UserDataModel $currentUser, CategoryDataModel $category, int $useCount): void
    {
        $violations = [];
        $violations = CategoryEditableConstraint::validate($category, $violations);
        $violations = CategoryUnusedConstraint::validate($useCount, $violations);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
