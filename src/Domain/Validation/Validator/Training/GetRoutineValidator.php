<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Training;

use App\Domain\DTO\DataModel\Training\RoutineDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;

/** What `GetRoutineUseCase` checks before it acts: that the routine is the caller's. */
final readonly class GetRoutineValidator
{
    public const string ERROR_CODE = 'get_routine_invalid';

    /**
     * @throws ValidationException
     */
    public function validate(UserDataModel $currentUser, RoutineDataModel $routine): void
    {
        $access = IsOwnerConstraint::validate($currentUser, $routine->owner);
        if ([] !== $access) {
            throw new ValidationException(self::ERROR_CODE, $access);
        }
    }
}
