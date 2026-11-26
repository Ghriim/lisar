<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Category\Admin;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Category\CategoryUnusedConstraint;
use App\Domain\Validation\Constraint\Common\IsAdminConstraint;

/**
 * `$useCount` is how many things — tasks across every account, catalogue habits — sit in the category.
 */
final readonly class DeleteReferenceCategoryValidator
{
    public const string ERROR_CODE = 'delete_reference_category_invalid';

    /**
     * @throws ValidationException
     */
    public function validate(UserDataModel $currentUser, int $useCount): void
    {
        $access = IsAdminConstraint::validate($currentUser);
        if ([] !== $access) {
            throw new ValidationException(self::ERROR_CODE, $access);
        }

        $violations = [];
        $violations = CategoryUnusedConstraint::validate($useCount, $violations);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
