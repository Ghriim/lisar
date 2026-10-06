<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Workout;

use App\Domain\DTO\DataModel\MovementDataModel;
use App\Domain\DTO\Input\Workout\AddWorkoutExerciseDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Workout\WorkoutMovementsOfferedConstraint;
use App\Domain\Validation\Validator\AbstractBaseValidator;

/**
 * `$offered` is the movement asked for, when it is offered.
 *
 * @extends AbstractBaseValidator<AddWorkoutExerciseDataInput>
 */
final readonly class AddWorkoutExerciseValidator extends AbstractBaseValidator
{
    public const string ERROR_CODE = 'add_workout_exercise_invalid';

    /**
     * @throws ValidationException
     */
    public function validate(AddWorkoutExerciseDataInput $input, ?MovementDataModel $offered): void
    {
        $violations = $this->getViolations($input);
        $violations = WorkoutMovementsOfferedConstraint::validate([$input->movementId], null === $offered ? [] : [$offered], 'movementId', $violations);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
