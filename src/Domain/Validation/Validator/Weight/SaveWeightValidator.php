<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Weight;

use App\Domain\DTO\Input\Weight\SaveWeightDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Validator\AbstractBaseValidator;

/**
 * @extends AbstractBaseValidator<SaveWeightDataInput>
 */
final readonly class SaveWeightValidator extends AbstractBaseValidator
{
    public const string ERROR_CODE = 'save_weight_invalid';

    /**
     * @throws ValidationException
     */
    public function validate(SaveWeightDataInput $input): void
    {
        $violations = $this->getViolations($input);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
