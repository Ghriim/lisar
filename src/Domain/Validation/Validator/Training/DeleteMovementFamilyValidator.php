<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Training;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsAdminConstraint;
use App\Domain\Validation\Constraint\Training\MovementFamilyUnusedConstraint;

/**
 * `$movementCount` is how many movements sit in the family.
 */
final readonly class DeleteMovementFamilyValidator
{
    public const string ERROR_CODE = 'delete_movement_family_invalid';

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
        $violations = MovementFamilyUnusedConstraint::validate($movementCount, $violations);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
