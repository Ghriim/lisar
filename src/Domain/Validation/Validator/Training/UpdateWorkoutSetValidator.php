<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Training;

use App\Domain\DTO\DataModel\Training\SetTypeDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutSetDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\UpdateWorkoutSetDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Domain\Validation\Constraint\Training\WorkoutSetInWorkoutConstraint;
use App\Domain\Validation\Constraint\Training\WorkoutSetMeasuresConstraint;
use App\Domain\Validation\Constraint\Training\WorkoutSetTypeUsableConstraint;
use App\Domain\Validation\Validator\AbstractBaseValidator;

/**
 * `$set` is the one corrected; `$setType` the row behind the id asked for, if any.
 *
 * @extends AbstractBaseValidator<UpdateWorkoutSetDataInput>
 */
final readonly class UpdateWorkoutSetValidator extends AbstractBaseValidator
{
    public const string ERROR_CODE = 'update_workout_set_invalid';

    /**
     * @throws ValidationException
     */
    public function validate(UserDataModel $currentUser, WorkoutDataModel $workout, WorkoutSetDataModel $set, UpdateWorkoutSetDataInput $input, ?SetTypeDataModel $setType): void
    {
        $access = IsOwnerConstraint::validate($currentUser, $workout->owner);
        if ([] !== $access) {
            throw new ValidationException(self::ERROR_CODE, $access);
        }

        // Alone as well: nothing is said about what another workout holds.
        $part = WorkoutSetInWorkoutConstraint::validate($set, $workout);
        if ([] !== $part) {
            throw new ValidationException(self::ERROR_CODE, $part);
        }

        $violations = $this->getViolations($input);
        $violations = WorkoutSetMeasuresConstraint::validate($set->exercise->movement, $input->reps, $input->weightInKilograms, $input->durationInSeconds, $input->distanceInMetres, $violations);
        $violations = WorkoutSetTypeUsableConstraint::validate($input->setTypeId, $setType, $set->setType, $violations);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
