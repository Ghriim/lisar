<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Step;

use App\Domain\DTO\Input\Step\SaveStepDayDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Validator\AbstractBaseValidator;

/**
 * @extends AbstractBaseValidator<SaveStepDayDataInput>
 */
final readonly class SaveStepDayValidator extends AbstractBaseValidator
{
    public const string ERROR_CODE = 'save_step_day_invalid';

    /**
     * @throws ValidationException
     */
    public function validate(SaveStepDayDataInput $input): void
    {
        $violations = $this->getViolations($input);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
