<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Category;

use App\Domain\DTO\DataModel\Category\CategoryDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Category\UpdateCategoryDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Category\CategoryEditableConstraint;
use App\Domain\Validation\Constraint\Category\CategoryLabelAvailableConstraint;
use App\Domain\Validation\Validator\AbstractBaseValidator;

/**
 * @extends AbstractBaseValidator<UpdateCategoryDataInput>
 */
final readonly class UpdateCategoryValidator extends AbstractBaseValidator
{
    public const string ERROR_CODE = 'update_category_invalid';

    /**
     * @throws ValidationException
     */
    public function validate(
        UserDataModel $currentUser,
        UpdateCategoryDataInput $input,
        CategoryDataModel $category,
        ?CategoryDataModel $referenceWithSameLabel,
        ?CategoryDataModel $personalWithSameLabel,
    ): void {
        $violations = $this->getViolations($input);
        $violations = CategoryEditableConstraint::validate($category, $violations);
        $violations = CategoryLabelAvailableConstraint::validate(
            $referenceWithSameLabel,
            $personalWithSameLabel,
            $category->id,
            $violations,
        );

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
