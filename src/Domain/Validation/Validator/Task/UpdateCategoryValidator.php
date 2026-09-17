<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Task;

use App\Domain\DTO\DataModel\CategoryDataModel;
use App\Domain\DTO\Input\Task\UpdateCategoryDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Task\CategoryEditableConstraint;
use App\Domain\Validation\Constraint\Task\CategoryLabelAvailableConstraint;
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
