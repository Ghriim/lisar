<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Training;

use App\Domain\DTO\DataModel\Training\MovementFamilyDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\UpdateMovementFamilyDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsAdminConstraint;
use App\Domain\Validation\Constraint\Training\MovementFamilyNameAvailableConstraint;
use App\Domain\Validation\Validator\AbstractBaseValidator;

/**
 * @extends AbstractBaseValidator<UpdateMovementFamilyDataInput>
 */
final readonly class UpdateMovementFamilyValidator extends AbstractBaseValidator
{
    public const string ERROR_CODE = 'update_movement_family_invalid';

    /**
     * @throws ValidationException
     */
    public function validate(UserDataModel $currentUser, UpdateMovementFamilyDataInput $input, MovementFamilyDataModel $movementFamily, ?MovementFamilyDataModel $withSameName): void
    {
        $access = IsAdminConstraint::validate($currentUser);
        if ([] !== $access) {
            throw new ValidationException(self::ERROR_CODE, $access);
        }

        $violations = $this->getViolations($input);
        $violations = MovementFamilyNameAvailableConstraint::validate($withSameName, $movementFamily->id, $violations);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
