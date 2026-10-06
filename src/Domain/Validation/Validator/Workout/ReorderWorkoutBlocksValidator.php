<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Workout;

use App\Domain\DTO\DataModel\WorkoutDataModel;
use App\Domain\DTO\Input\Workout\ReorderWorkoutBlocksDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Workout\WorkoutBlockOrderConstraint;
use App\Domain\Validation\Validator\AbstractBaseValidator;

/**
 * The new order against the workout it reorders.
 *
 * @extends AbstractBaseValidator<ReorderWorkoutBlocksDataInput>
 */
final readonly class ReorderWorkoutBlocksValidator extends AbstractBaseValidator
{
    public const string ERROR_CODE = 'reorder_workout_blocks_invalid';

    /**
     * @throws ValidationException
     */
    public function validate(ReorderWorkoutBlocksDataInput $input, WorkoutDataModel $workout): void
    {
        $violations = $this->getViolations($input);
        $violations = WorkoutBlockOrderConstraint::validate($input->blockIds, $workout, $violations);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
