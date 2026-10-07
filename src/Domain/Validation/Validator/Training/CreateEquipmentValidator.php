<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Training;

use App\Domain\DTO\DataModel\Training\EquipmentDataModel;
use App\Domain\DTO\Input\Training\CreateEquipmentDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Training\EquipmentNameAvailableConstraint;
use App\Domain\Validation\Validator\AbstractBaseValidator;

/**
 * @extends AbstractBaseValidator<CreateEquipmentDataInput>
 */
final readonly class CreateEquipmentValidator extends AbstractBaseValidator
{
    public const string ERROR_CODE = 'create_equipment_invalid';

    /**
     * @param EquipmentDataModel|null $withSameName the row already carrying that name, ignoring case, if any
     *
     * @throws ValidationException
     */
    public function validate(CreateEquipmentDataInput $input, ?EquipmentDataModel $withSameName): void
    {
        $violations = $this->getViolations($input);
        $violations = EquipmentNameAvailableConstraint::validate($withSameName, null, $violations);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
