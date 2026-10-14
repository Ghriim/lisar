<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Training;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsAdminConstraint;
use App\Domain\Validation\Constraint\Training\EquipmentUnusedConstraint;

/**
 * `$movementCount` is how many movements use the equipment.
 */
final readonly class DeleteEquipmentValidator
{
    public const string ERROR_CODE = 'delete_equipment_invalid';

    /**
     * @throws ValidationException
     */
    public function validate(UserDataModel $currentUser, int $movementCount): void
    {
        $access = IsAdminConstraint::validate($currentUser);
        if ([] !== $access) {
            throw new ValidationException(self::ERROR_CODE, $access);
        }

        $violations = [];
        $violations = EquipmentUnusedConstraint::validate($movementCount, $violations);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
