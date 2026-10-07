<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Todo\Admin;

use App\Domain\DTO\DataModel\Todo\CategoryDataModel;
use App\Domain\DTO\Input\Todo\Admin\UpdateReferenceCategoryDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Todo\CategoryLabelAvailableConstraint;
use App\Domain\Validation\Validator\AbstractBaseValidator;

/**
 * @extends AbstractBaseValidator<UpdateReferenceCategoryDataInput>
 */
final readonly class UpdateReferenceCategoryValidator extends AbstractBaseValidator
{
    public const string ERROR_CODE = 'update_reference_category_invalid';

    /**
     * @throws ValidationException
     */
    public function validate(
        UpdateReferenceCategoryDataInput $input,
        CategoryDataModel $category,
        ?CategoryDataModel $withSameLabel,
    ): void {
        $violations = $this->getViolations($input);
        $violations = CategoryLabelAvailableConstraint::validate(
            $withSameLabel,
            null,
            $category->id,
            $violations,
        );

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
