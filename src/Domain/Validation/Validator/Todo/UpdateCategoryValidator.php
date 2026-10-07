<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Todo;

use App\Domain\DTO\DataModel\Todo\CategoryDataModel;
use App\Domain\DTO\Input\Todo\UpdateCategoryDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Todo\CategoryEditableConstraint;
use App\Domain\Validation\Constraint\Todo\CategoryLabelAvailableConstraint;
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
