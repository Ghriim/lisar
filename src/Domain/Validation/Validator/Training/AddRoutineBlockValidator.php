<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Training;

use App\Domain\DTO\DataModel\Training\MovementDataModel;
use App\Domain\DTO\DataModel\Training\RoutineDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\AddRoutineBlockDataInput;
use App\Domain\DTO\Input\Training\AddRoutineBlockExerciseDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Domain\Validation\Constraint\Training\RoutineBlockMovementsDistinctConstraint;
use App\Domain\Validation\Constraint\Training\RoutineMovementsSupportedConstraint;
use App\Domain\Validation\Constraint\Training\WorkoutMovementsOfferedConstraint;
use App\Domain\Validation\Validator\AbstractBaseValidator;

/**
 * What `AddRoutineBlockUseCase` checks before it acts: that the routine is the caller's, then that
 * every movement asked for is offered, fits a routine, and comes once. `$offered` is the offered
 * movements among those asked for.
 *
 * @extends AbstractBaseValidator<AddRoutineBlockDataInput>
 */
final readonly class AddRoutineBlockValidator extends AbstractBaseValidator
{
    public const string ERROR_CODE = 'add_routine_block_invalid';

    /**
     * @param list<MovementDataModel> $offered
     *
     * @throws ValidationException
     */
    public function validate(UserDataModel $currentUser, RoutineDataModel $routine, AddRoutineBlockDataInput $input, array $offered): void
    {
        $access = IsOwnerConstraint::validate($currentUser, $routine->owner);
        if ([] !== $access) {
            throw new ValidationException(self::ERROR_CODE, $access);
        }

        $movementIds = array_map(static fn (AddRoutineBlockExerciseDataInput $exercise): int => $exercise->movementId, $input->exercises);

        $violations = $this->getViolations($input);
        $violations = WorkoutMovementsOfferedConstraint::validate($movementIds, $offered, 'exercises', $violations);
        $violations = RoutineMovementsSupportedConstraint::validate($offered, 'exercises', $violations);
        $violations = RoutineBlockMovementsDistinctConstraint::validate($movementIds, $violations);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
