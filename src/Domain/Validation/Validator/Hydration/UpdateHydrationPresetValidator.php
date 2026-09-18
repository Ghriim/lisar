<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Hydration;

use App\Domain\DTO\Input\Hydration\UpdateHydrationPresetDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Validator\AbstractBaseValidator;

/**
 * @extends AbstractBaseValidator<UpdateHydrationPresetDataInput>
 */
final readonly class UpdateHydrationPresetValidator extends AbstractBaseValidator
{
    public const string ERROR_CODE = 'update_hydration_preset_invalid';

    /**
     * @throws ValidationException
     */
    public function validate(UpdateHydrationPresetDataInput $input): void
    {
        $violations = $this->getViolations($input);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
