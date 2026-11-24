<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Planning;

use App\Domain\DTO\Input\Planning\CreatePlannedTasksDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Validator\AbstractBaseValidator;

/**
 * No caller: the run is the system's, on a schedule or replayed by hand from the console.
 *
 * @extends AbstractBaseValidator<CreatePlannedTasksDataInput>
 */
final readonly class CreatePlannedTasksValidator extends AbstractBaseValidator
{
    public const string ERROR_CODE = 'create_planned_tasks_invalid';

    /**
     * @throws ValidationException
     */
    public function validate(CreatePlannedTasksDataInput $input): void
    {
        $violations = $this->getViolations($input);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
