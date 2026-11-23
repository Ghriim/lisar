<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Training;

use App\Domain\DTO\DataModel\Training\MovementDataModel;
use App\Domain\DTO\DataModel\Training\RoutineBlockDataModel;
use App\Domain\DTO\DataModel\Training\RoutineDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\AddRoutineExerciseDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Domain\Validation\Constraint\Training\RoutineBlockInRoutineConstraint;
use App\Domain\Validation\Constraint\Training\RoutineMovementsSupportedConstraint;
use App\Domain\Validation\Constraint\Training\WorkoutMovementsOfferedConstraint;
use App\Domain\Validation\Validator\AbstractBaseValidator;

/**
 * What `AddRoutineExerciseUseCase` checks before it acts: that the routine is the caller's and the
 * block one of its own, then that the movement is offered and fits a routine. `$offered` is the
 * movement asked for, when it is offered.
 *
 * @extends AbstractBaseValidator<AddRoutineExerciseDataInput>
 */
final readonly class AddRoutineExerciseValidator extends AbstractBaseValidator
{
    public const string ERROR_CODE = 'add_routine_exercise_invalid';

    /**
     * @throws ValidationException
     */
    public function validate(UserDataModel $currentUser, RoutineDataModel $routine, RoutineBlockDataModel $block, AddRoutineExerciseDataInput $input, ?MovementDataModel $offered): void
    {
        $access = IsOwnerConstraint::validate($currentUser, $routine->owner);
        if ([] !== $access) {
            throw new ValidationException(self::ERROR_CODE, $access);
        }

        // Alone as well: nothing is said about what another routine holds.
        $part = RoutineBlockInRoutineConstraint::validate($block, $routine);
        if ([] !== $part) {
            throw new ValidationException(self::ERROR_CODE, $part);
        }

        $offeredList = null === $offered ? [] : [$offered];

        $violations = $this->getViolations($input);
        $violations = WorkoutMovementsOfferedConstraint::validate([$input->movementId], $offeredList, 'movementId', $violations);
        $violations = RoutineMovementsSupportedConstraint::validate($offeredList, 'movementId', $violations);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
