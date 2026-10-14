<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Habits;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Habits\CreateHabitDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsAdminConstraint;
use App\Domain\Validation\Constraint\Habits\HabitSourceConstraint;
use App\Domain\Validation\Validator\AbstractBaseValidator;

/**
 * @extends AbstractBaseValidator<CreateHabitDataInput>
 */
final readonly class CreateHabitValidator extends AbstractBaseValidator
{
    public const string ERROR_CODE = 'create_habit_invalid';

    /**
     * @throws ValidationException
     */
    public function validate(UserDataModel $currentUser, CreateHabitDataInput $input): void
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

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
