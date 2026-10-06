<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Workout;

use App\Domain\DTO\DataModel\SetTypeDataModel;
use App\Domain\DTO\DataModel\WorkoutSetDataModel;
use App\Domain\DTO\Input\Workout\UpdateWorkoutSetDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Workout\WorkoutSetMeasuresConstraint;
use App\Domain\Validation\Constraint\Workout\WorkoutSetTypeUsableConstraint;
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
    public function validate(UpdateWorkoutSetDataInput $input, WorkoutSetDataModel $set, ?SetTypeDataModel $setType): void
    {
        $violations = $this->getViolations($input);
        $violations = WorkoutSetMeasuresConstraint::validate($set->exercise->movement, $input->reps, $input->weightInKilograms, $input->durationInSeconds, $input->distanceInMetres, $violations);
        $violations = WorkoutSetTypeUsableConstraint::validate($input->setTypeId, $setType, $set->setType, $violations);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
