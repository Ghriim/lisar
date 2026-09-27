<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Workout;

use App\Domain\DTO\DataModel\MovementFamilyDataModel;
use App\Domain\DTO\Input\Workout\UpdateMovementFamilyDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Workout\MovementFamilyNameAvailableConstraint;
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
    public function validate(UpdateMovementFamilyDataInput $input, MovementFamilyDataModel $movementFamily, ?MovementFamilyDataModel $withSameName): void
    {
        $violations = $this->getViolations($input);
        $violations = MovementFamilyNameAvailableConstraint::validate($withSameName, $movementFamily->id, $violations);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
