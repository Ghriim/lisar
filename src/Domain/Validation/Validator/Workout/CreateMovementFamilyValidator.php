<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Workout;

use App\Domain\DTO\DataModel\MovementFamilyDataModel;
use App\Domain\DTO\Input\Workout\CreateMovementFamilyDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Workout\MovementFamilyNameAvailableConstraint;
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
    public function validate(CreateMovementFamilyDataInput $input, ?MovementFamilyDataModel $withSameName): void
    {
        $violations = $this->getViolations($input);
        $violations = MovementFamilyNameAvailableConstraint::validate($withSameName, null, $violations);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
