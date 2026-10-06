<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Workout;

use App\Domain\DTO\DataModel\MovementDataModel;
use App\Domain\DTO\DataModel\SetTypeDataModel;
use App\Domain\DTO\Input\Workout\AddWorkoutSetDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Workout\WorkoutSetMeasuresConstraint;
use App\Domain\Validation\Constraint\Workout\WorkoutSetTypeUsableConstraint;
use App\Domain\Validation\Validator\AbstractBaseValidator;

/**
 * `$movement` is the one the set is logged on; `$setType` the row behind the id asked for, if any.
 *
 * @extends AbstractBaseValidator<AddWorkoutSetDataInput>
 */
final readonly class AddWorkoutSetValidator extends AbstractBaseValidator
{
    public const string ERROR_CODE = 'add_workout_set_invalid';

    /**
     * @throws ValidationException
     */
    public function validate(AddWorkoutSetDataInput $input, MovementDataModel $movement, ?SetTypeDataModel $setType): void
    {
        $violations = $this->getViolations($input);
        $violations = WorkoutSetMeasuresConstraint::validate($movement, $input->reps, $input->weightInKilograms, $input->durationInSeconds, $input->distanceInMetres, $violations);
        $violations = WorkoutSetTypeUsableConstraint::validate($input->setTypeId, $setType, null, $violations);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
