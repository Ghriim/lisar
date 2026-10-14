<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Training;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\Admin\ListMusclesForAdminDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsAdminConstraint;
use App\Domain\Validation\Validator\AbstractBaseValidator;

/**
 * The filters' shape. No rule beyond it yet: the next one lands here, as a Constraint.
 *
 * @extends AbstractBaseValidator<ListMusclesForAdminDataInput>
 */
final readonly class ListMusclesForAdminValidator extends AbstractBaseValidator
{
    public const string ERROR_CODE = 'list_muscles_for_admin_invalid';

    /**
     * @throws ValidationException
     */
    public function validate(UserDataModel $currentUser, ListMusclesForAdminDataInput $input): void
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
