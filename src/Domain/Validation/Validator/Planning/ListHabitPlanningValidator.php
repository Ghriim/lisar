<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Planning;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Planning\ListPlanningDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Planning\PlanningRangeConstraint;
use App\Domain\Validation\Validator\AbstractBaseValidator;

/** @extends AbstractBaseValidator<ListPlanningDataInput> */
final readonly class ListHabitPlanningValidator extends AbstractBaseValidator
{
    public const string ERROR_CODE = 'list_habit_planning_invalid';

    /**
     * @throws ValidationException
     */
    public function validate(UserDataModel $currentUser, ListPlanningDataInput $input): void
    {
        $violations = $this->getViolations($input);
        $violations = PlanningRangeConstraint::validate($input->from, $input->to, $violations);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
