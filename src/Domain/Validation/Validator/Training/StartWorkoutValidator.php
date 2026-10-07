<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Training;

use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\Input\Training\StartWorkoutDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Training\WorkoutNotInProgressConstraint;
use App\Domain\Validation\Validator\AbstractBaseValidator;

/**
 * `$inProgress` is the owner's workout in progress, if any.
 *
 * @extends AbstractBaseValidator<StartWorkoutDataInput>
 */
final readonly class StartWorkoutValidator extends AbstractBaseValidator
{
    public const string ERROR_CODE = 'start_workout_invalid';

    /**
     * @throws ValidationException
     */
    public function validate(StartWorkoutDataInput $input, ?WorkoutDataModel $inProgress): void
    {
        $violations = $this->getViolations($input);
        $violations = WorkoutNotInProgressConstraint::validate($inProgress, $violations);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
