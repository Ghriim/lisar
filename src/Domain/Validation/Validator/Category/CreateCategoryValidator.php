<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Category;

use App\Domain\DTO\DataModel\Category\CategoryDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Category\CreateCategoryDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Category\CategoryLabelAvailableConstraint;
use App\Domain\Validation\Validator\AbstractBaseValidator;

/**
 * @extends AbstractBaseValidator<CreateCategoryDataInput>
 */
final readonly class CreateCategoryValidator extends AbstractBaseValidator
{
    public const string ERROR_CODE = 'create_category_invalid';

    /**
     * @throws ValidationException
     */
    public function validate(
        UserDataModel $currentUser,
        CreateCategoryDataInput $input,
        ?CategoryDataModel $referenceWithSameLabel,
        ?CategoryDataModel $personalWithSameLabel,
    ): void {
        $violations = $this->getViolations($input);
        $violations = CategoryLabelAvailableConstraint::validate(
            $referenceWithSameLabel,
            $personalWithSameLabel,
            null,
            $violations,
        );

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
