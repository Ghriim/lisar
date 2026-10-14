<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Training;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\Admin\ListSetTypesForAdminDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsAdminConstraint;
use App\Domain\Validation\Validator\AbstractBaseValidator;

/**
 * The filters' shape. No rule beyond it yet: the next one lands here, as a Constraint.
 *
 * @extends AbstractBaseValidator<ListSetTypesForAdminDataInput>
 */
final readonly class ListSetTypesForAdminValidator extends AbstractBaseValidator
{
    public const string ERROR_CODE = 'list_set_types_for_admin_invalid';

    /**
     * @throws ValidationException
     */
    public function validate(UserDataModel $currentUser, ListSetTypesForAdminDataInput $input): void
    {
        $access = IsAdminConstraint::validate($currentUser);
        if ([] !== $access) {
            throw new ValidationException(self::ERROR_CODE, $access);
        }

        $violations = $this->getViolations($input);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
