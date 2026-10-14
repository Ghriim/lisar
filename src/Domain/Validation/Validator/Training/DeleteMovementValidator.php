<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Training;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsAdminConstraint;
use App\Domain\Validation\Constraint\Training\MovementUnusedConstraint;

/**
 * `$exerciseCount` is how many workout exercises, anyone's, use the movement.
 */
final readonly class DeleteMovementValidator
{
    public const string ERROR_CODE = 'delete_movement_invalid';

    /**
     * @throws ValidationException
     */
    public function validate(UserDataModel $currentUser, int $exerciseCount): void
    {
        $access = IsAdminConstraint::validate($currentUser);
        if ([] !== $access) {
            throw new ValidationException(self::ERROR_CODE, $access);
        }

        $violations = [];
        $violations = MovementUnusedConstraint::validate($exerciseCount, $violations);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
