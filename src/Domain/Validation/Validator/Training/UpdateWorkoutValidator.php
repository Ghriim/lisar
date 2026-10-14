<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Training;

use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\UpdateWorkoutDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Domain\Validation\Validator\AbstractBaseValidator;

/**
 * The shape rules only: a workout's name, note and feeling depend on nothing else.
 *
 * @extends AbstractBaseValidator<UpdateWorkoutDataInput>
 */
final readonly class UpdateWorkoutValidator extends AbstractBaseValidator
{
    public const string ERROR_CODE = 'update_workout_invalid';

    /**
     * @throws ValidationException
     */
    public function validate(UserDataModel $currentUser, WorkoutDataModel $workout, UpdateWorkoutDataInput $input): void
    {
        $access = IsOwnerConstraint::validate($currentUser, $workout->owner);
        if ([] !== $access) {
            throw new ValidationException(self::ERROR_CODE, $access);
        }

        $violations = $this->getViolations($input);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
