<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Planning;

use App\Domain\DTO\DataModel\Training\RoutineDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Planning\SaveTrainingPlanningEntryDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Planning\PlanningScheduleConstraint;
use App\Domain\Validation\Constraint\Planning\RoutineUsableConstraint;
use App\Domain\Validation\Validator\AbstractBaseValidator;

/**
 * `$routine` is the routine the input asks for, if it exists, whoever it belongs to.
 *
 * @extends AbstractBaseValidator<SaveTrainingPlanningEntryDataInput>
 */
final readonly class CreateTrainingPlanningEntryValidator extends AbstractBaseValidator
{
    public const string ERROR_CODE = 'create_training_planning_entry_invalid';

    /**
     * @throws ValidationException
     */
    public function validate(UserDataModel $currentUser, SaveTrainingPlanningEntryDataInput $input, ?RoutineDataModel $routine): void
    {
        $violations = $this->getViolations($input);
        $violations = RoutineUsableConstraint::validate($routine, $currentUser, $violations);
        $violations = PlanningScheduleConstraint::validate($input->schedule, 'schedule', $violations);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
