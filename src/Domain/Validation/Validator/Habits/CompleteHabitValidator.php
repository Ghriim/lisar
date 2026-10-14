<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Habits;

use App\Domain\DTO\DataModel\Habits\HabitDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Habits\HabitManualConstraint;

final readonly class CompleteHabitValidator
{
    public const string ERROR_CODE = 'complete_habit_invalid';

    /**
     * @throws ValidationException
     */
    public function validate(UserDataModel $currentUser, HabitDataModel $habit): void
    {
        $violations = [];
        $violations = HabitManualConstraint::validate($habit, $violations);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
