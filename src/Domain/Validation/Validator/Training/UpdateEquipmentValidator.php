<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Training;

use App\Domain\DTO\DataModel\Training\EquipmentDataModel;
use App\Domain\DTO\Input\Training\UpdateEquipmentDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Training\EquipmentNameAvailableConstraint;
use App\Domain\Validation\Validator\AbstractBaseValidator;

/**
 * @extends AbstractBaseValidator<UpdateEquipmentDataInput>
 */
final readonly class UpdateEquipmentValidator extends AbstractBaseValidator
{
    public const string ERROR_CODE = 'update_equipment_invalid';

    /**
     * @throws ValidationException
     */
    public function validate(UpdateEquipmentDataInput $input, EquipmentDataModel $equipment, ?EquipmentDataModel $withSameName): void
    {
        $violations = $this->getViolations($input);
        $violations = EquipmentNameAvailableConstraint::validate($withSameName, $equipment->id, $violations);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
