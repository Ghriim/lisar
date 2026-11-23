<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Training;

use App\Domain\DTO\DataModel\Training\RoutineDataModel;
use App\Domain\DTO\DataModel\Training\RoutineSetDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\UpdateRoutineSetDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Domain\Validation\Constraint\Training\RoutineSetInRoutineConstraint;
use App\Domain\Validation\Constraint\Training\RoutineSetPrescriptionConstraint;
use App\Domain\Validation\Validator\AbstractBaseValidator;

/**
 * What `UpdateRoutineSetUseCase` checks before it acts: that the routine is the caller's and the set one of its own, then what it prescribes.
 *
 * @extends AbstractBaseValidator<UpdateRoutineSetDataInput>
 */
final readonly class UpdateRoutineSetValidator extends AbstractBaseValidator
{
    public const string ERROR_CODE = 'update_routine_set_invalid';

    /**
     * @throws ValidationException
     */
    public function validate(UserDataModel $currentUser, RoutineDataModel $routine, RoutineSetDataModel $set, UpdateRoutineSetDataInput $input): void
    {
        $access = IsOwnerConstraint::validate($currentUser, $routine->owner);
        if ([] !== $access) {
            throw new ValidationException(self::ERROR_CODE, $access);
        }

        // Alone as well: nothing is said about what another routine holds.
        $part = RoutineSetInRoutineConstraint::validate($set, $routine);
        if ([] !== $part) {
            throw new ValidationException(self::ERROR_CODE, $part);
        }

        $violations = $this->getViolations($input);
        $violations = RoutineSetPrescriptionConstraint::validate($set->exercise->movement, $input->reps, $input->percentageOfOneRepMax, $violations);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
