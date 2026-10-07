<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Habits;

use App\Domain\DTO\Input\Habits\CreateHabitDataInput;
use App\Domain\Exception\ValidationException;
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
    public function validate(CreateHabitDataInput $input): void
    {
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
