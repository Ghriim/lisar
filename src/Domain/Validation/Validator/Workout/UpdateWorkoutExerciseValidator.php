<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Workout;

use App\Domain\DTO\Input\Workout\UpdateWorkoutExerciseDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Validator\AbstractBaseValidator;

/**
 * The shape rules only: a note depends on nothing else.
 *
 * @extends AbstractBaseValidator<UpdateWorkoutExerciseDataInput>
 */
final readonly class UpdateWorkoutExerciseValidator extends AbstractBaseValidator
{
    public const string ERROR_CODE = 'update_workout_exercise_invalid';

    /**
     * @throws ValidationException
     */
    public function validate(UpdateWorkoutExerciseDataInput $input): void
    {
        $violations = $this->getViolations($input);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
