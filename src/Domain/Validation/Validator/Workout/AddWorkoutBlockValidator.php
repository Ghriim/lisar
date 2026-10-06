<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Workout;

use App\Domain\DTO\DataModel\MovementDataModel;
use App\Domain\DTO\Input\Workout\AddWorkoutBlockDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Workout\WorkoutMovementsOfferedConstraint;
use App\Domain\Validation\Validator\AbstractBaseValidator;

/**
 * `$offered` holds the offered movements among the ids asked for.
 *
 * @extends AbstractBaseValidator<AddWorkoutBlockDataInput>
 */
final readonly class AddWorkoutBlockValidator extends AbstractBaseValidator
{
    public const string ERROR_CODE = 'add_workout_block_invalid';

    /**
     * @param list<MovementDataModel> $offered
     *
     * @throws ValidationException
     */
    public function validate(AddWorkoutBlockDataInput $input, array $offered): void
    {
        $violations = $this->getViolations($input);
        $violations = WorkoutMovementsOfferedConstraint::validate($input->movementIds, $offered, 'movementIds', $violations);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
