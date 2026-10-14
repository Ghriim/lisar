<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Training;

use App\Domain\DTO\DataModel\Training\MovementDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\AddWorkoutBlockDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Domain\Validation\Constraint\Training\WorkoutMovementsOfferedConstraint;
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
    public function validate(UserDataModel $currentUser, WorkoutDataModel $workout, AddWorkoutBlockDataInput $input, array $offered): void
    {
        $access = IsOwnerConstraint::validate($currentUser, $workout->owner);
        if ([] !== $access) {
            throw new ValidationException(self::ERROR_CODE, $access);
        }

        $violations = $this->getViolations($input);
        $violations = WorkoutMovementsOfferedConstraint::validate($input->movementIds(), $offered, 'exercises', $violations);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
