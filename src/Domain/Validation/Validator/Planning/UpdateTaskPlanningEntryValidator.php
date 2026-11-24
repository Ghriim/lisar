<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Planning;

use App\Domain\DTO\DataModel\Planning\TaskPlanningEntryDataModel;
use App\Domain\DTO\DataModel\Todo\CategoryDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Planning\SaveTaskPlanningEntryDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Domain\Validation\Constraint\Planning\PlanningScheduleConstraint;
use App\Domain\Validation\Constraint\Todo\CategoryUsableConstraint;
use App\Domain\Validation\Validator\AbstractBaseValidator;

/**
 * `$category` is the category the input asks for, already loaded, if any.
 *
 * @extends AbstractBaseValidator<SaveTaskPlanningEntryDataInput>
 */
final readonly class UpdateTaskPlanningEntryValidator extends AbstractBaseValidator
{
    public const string ERROR_CODE = 'update_task_planning_entry_invalid';

    /**
     * @throws ValidationException
     */
    public function validate(UserDataModel $currentUser, TaskPlanningEntryDataModel $entry, SaveTaskPlanningEntryDataInput $input, ?CategoryDataModel $category): void
    {
        $access = IsOwnerConstraint::validate($currentUser, $entry->owner);
        if ([] !== $access) {
            throw new ValidationException(self::ERROR_CODE, $access);
        }

        $violations = $this->getViolations($input);
        $violations = CategoryUsableConstraint::validate($input->categoryId, $category, $currentUser, $violations);
        $violations = PlanningScheduleConstraint::validate($input->schedule, 'schedule', $violations);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
