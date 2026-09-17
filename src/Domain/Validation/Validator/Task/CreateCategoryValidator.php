<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Task;

use App\Domain\DTO\DataModel\CategoryDataModel;
use App\Domain\DTO\Input\Task\CreateCategoryDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Task\CategoryLabelAvailableConstraint;
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
