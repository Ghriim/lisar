<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Hydration;

use App\Domain\DTO\Input\Hydration\CreateHydrationPresetDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Validator\AbstractBaseValidator;

/**
 * @extends AbstractBaseValidator<CreateHydrationPresetDataInput>
 */
final readonly class CreateHydrationPresetValidator extends AbstractBaseValidator
{
    public const string ERROR_CODE = 'create_hydration_preset_invalid';

    /**
     * @throws ValidationException
     */
    public function validate(CreateHydrationPresetDataInput $input): void
    {
        $violations = $this->getViolations($input);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
