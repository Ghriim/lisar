<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Training;

use App\Domain\DTO\DataModel\Training\MovementFamilyDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\CreateMovementFamilyDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsAdminConstraint;
use App\Domain\Validation\Constraint\Training\MovementFamilyNameAvailableConstraint;
use App\Domain\Validation\Validator\AbstractBaseValidator;

/**
 * @extends AbstractBaseValidator<CreateMovementFamilyDataInput>
 */
final readonly class CreateMovementFamilyValidator extends AbstractBaseValidator
{
    public const string ERROR_CODE = 'create_movement_family_invalid';

    /**
     * @param MovementFamilyDataModel|null $withSameName the row already carrying that name, ignoring case, if any
     *
     * @throws ValidationException
     */
    public function validate(UserDataModel $currentUser, CreateMovementFamilyDataInput $input, ?MovementFamilyDataModel $withSameName): void
    {
        $access = IsAdminConstraint::validate($currentUser);
        if ([] !== $access) {
            throw new ValidationException(self::ERROR_CODE, $access);
        }

        $violations = $this->getViolations($input);
        $violations = MovementFamilyNameAvailableConstraint::validate($withSameName, null, $violations);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
