<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Planning;

use App\Domain\DTO\DataModel\Habits\HabitSubscriptionDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Planning\SaveHabitPlanningEntryDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Planning\HabitSubscribedConstraint;
use App\Domain\Validation\Constraint\Planning\PlanningScheduleConstraint;
use App\Domain\Validation\Validator\AbstractBaseValidator;

/**
 * `$subscription` is the caller's subscription to the habit the input asks for, if they have one.
 *
 * @extends AbstractBaseValidator<SaveHabitPlanningEntryDataInput>
 */
final readonly class CreateHabitPlanningEntryValidator extends AbstractBaseValidator
{
    public const string ERROR_CODE = 'create_habit_planning_entry_invalid';

    /**
     * @throws ValidationException
     */
    public function validate(UserDataModel $currentUser, SaveHabitPlanningEntryDataInput $input, ?HabitSubscriptionDataModel $subscription): void
    {
        $violations = $this->getViolations($input);
        $violations = HabitSubscribedConstraint::validate($subscription, $currentUser, $violations);
        $violations = PlanningScheduleConstraint::validate($input->schedule, 'schedule', $violations);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
