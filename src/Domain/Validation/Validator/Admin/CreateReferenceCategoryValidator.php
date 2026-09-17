<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Admin;

use App\Domain\DTO\DataModel\CategoryDataModel;
use App\Domain\DTO\Input\Admin\CreateReferenceCategoryDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Task\CategoryLabelAvailableConstraint;
use App\Domain\Validation\Validator\AbstractBaseValidator;

/**
 * @extends AbstractBaseValidator<CreateReferenceCategoryDataInput>
 */
final readonly class CreateReferenceCategoryValidator extends AbstractBaseValidator
{
    public const string ERROR_CODE = 'create_reference_category_invalid';

    /**
     * @param CategoryDataModel|null $withSameLabel the reference category already carrying that
     *                                              label, if any. Personal categories are not
     *                                              consulted: they are private, and a person
     *                                              already sees their own apart from the common
     *                                              ones.
     *
     * @throws ValidationException
     */
    public function validate(CreateReferenceCategoryDataInput $input, ?CategoryDataModel $withSameLabel): void
    {
        $violations = $this->getViolations($input);
        $violations = CategoryLabelAvailableConstraint::validate($withSameLabel, null, null, $violations);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
