<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Training;

use App\Domain\DTO\DataModel\Training\RoutineDataModel;
use App\Domain\DTO\DataModel\Training\RoutineExerciseDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\AddRoutineSetDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Domain\Validation\Constraint\Training\RoutineExerciseInRoutineConstraint;
use App\Domain\Validation\Constraint\Training\RoutineSetPrescriptionConstraint;
use App\Domain\Validation\Validator\AbstractBaseValidator;

/**
 * What `AddRoutineSetUseCase` checks before it acts: that the routine is the caller's and the movement one of its own, then what the set prescribes.
 *
 * @extends AbstractBaseValidator<AddRoutineSetDataInput>
 */
final readonly class AddRoutineSetValidator extends AbstractBaseValidator
{
    public const string ERROR_CODE = 'add_routine_set_invalid';

    /**
     * @throws ValidationException
     */
    public function validate(UserDataModel $currentUser, RoutineDataModel $routine, RoutineExerciseDataModel $exercise, AddRoutineSetDataInput $input): void
    {
        $access = IsOwnerConstraint::validate($currentUser, $routine->owner);
        if ([] !== $access) {
            throw new ValidationException(self::ERROR_CODE, $access);
        }

        // Alone as well: nothing is said about what another routine holds.
        $part = RoutineExerciseInRoutineConstraint::validate($exercise, $routine);
        if ([] !== $part) {
            throw new ValidationException(self::ERROR_CODE, $part);
        }

        $violations = $this->getViolations($input);
        $violations = RoutineSetPrescriptionConstraint::validate($exercise->movement, $input->reps, $input->percentageOfOneRepMax, $violations);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
