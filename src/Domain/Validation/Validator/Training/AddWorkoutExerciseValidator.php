<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Training;

use App\Domain\DTO\DataModel\Training\MovementDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\AddWorkoutExerciseDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Domain\Validation\Constraint\Training\WorkoutMovementsOfferedConstraint;
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
    public function validate(UserDataModel $currentUser, WorkoutDataModel $workout, AddWorkoutExerciseDataInput $input, ?MovementDataModel $offered): void
    {
        $access = IsOwnerConstraint::validate($currentUser, $workout->owner);
        if ([] !== $access) {
            throw new ValidationException(self::ERROR_CODE, $access);
        }

        $violations = $this->getViolations($input);
        $violations = WorkoutMovementsOfferedConstraint::validate([$input->movementId], null === $offered ? [] : [$offered], 'movementId', $violations);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
