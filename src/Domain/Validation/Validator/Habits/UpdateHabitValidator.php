<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Habits;

use App\Domain\DTO\DataModel\Category\CategoryDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Habits\UpdateHabitDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Category\CategoryCommonConstraint;
use App\Domain\Validation\Constraint\Common\IsAdminConstraint;
use App\Domain\Validation\Constraint\Habits\HabitSourceConstraint;
use App\Domain\Validation\Validator\AbstractBaseValidator;

/**
 * @extends AbstractBaseValidator<UpdateHabitDataInput>
 */
final readonly class UpdateHabitValidator extends AbstractBaseValidator
{
    public const string ERROR_CODE = 'update_habit_invalid';

    /**
     * @param CategoryDataModel|null $category the category the caller asked for, already loaded
     *
     * @throws ValidationException
     */
    public function validate(UserDataModel $currentUser, UpdateHabitDataInput $input, ?CategoryDataModel $category): void
    {
        $access = IsAdminConstraint::validate($currentUser);
        if ([] !== $access) {
            throw new ValidationException(self::ERROR_CODE, $access);
        }

        $violations = $this->getViolations($input);
        $violations = HabitSourceConstraint::validate(
            $input->sourceKind,
            $input->trackerKind,
            $input->trackerThreshold,
            $violations,
        );
        $violations = CategoryCommonConstraint::validate($input->categoryId, $category, $violations);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
