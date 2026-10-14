<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Todo\Admin;

use App\Domain\DTO\DataModel\Todo\CategoryDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Todo\Admin\CreateReferenceCategoryDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsAdminConstraint;
use App\Domain\Validation\Constraint\Todo\CategoryLabelAvailableConstraint;
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
    public function validate(UserDataModel $currentUser, CreateReferenceCategoryDataInput $input, ?CategoryDataModel $withSameLabel): void
    {
        $access = IsAdminConstraint::validate($currentUser);
        if ([] !== $access) {
            throw new ValidationException(self::ERROR_CODE, $access);
        }

        $violations = $this->getViolations($input);
        $violations = CategoryLabelAvailableConstraint::validate($withSameLabel, null, null, $violations);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
