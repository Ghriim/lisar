<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Training;

use App\Domain\DTO\DataModel\Training\RoutineDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\UpdateRoutineDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Domain\Validation\Validator\AbstractBaseValidator;

/**
 * What `UpdateRoutineUseCase` checks before it acts: that the routine is the caller's, then the name.
 *
 * @extends AbstractBaseValidator<UpdateRoutineDataInput>
 */
final readonly class UpdateRoutineValidator extends AbstractBaseValidator
{
    public const string ERROR_CODE = 'update_routine_invalid';

    /**
     * @throws ValidationException
     */
    public function validate(UserDataModel $currentUser, RoutineDataModel $routine, UpdateRoutineDataInput $input): void
    {
        $access = IsOwnerConstraint::validate($currentUser, $routine->owner);
        if ([] !== $access) {
            throw new ValidationException(self::ERROR_CODE, $access);
        }

        $violations = $this->getViolations($input);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
