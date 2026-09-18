<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Hydration;

use App\Domain\DTO\Input\Hydration\UpdateHydrationEntryDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Hydration\EntryFromTodayConstraint;
use App\Domain\Validation\Validator\AbstractBaseValidator;

/**
 * @extends AbstractBaseValidator<UpdateHydrationEntryDataInput>
 */
final readonly class UpdateHydrationEntryValidator extends AbstractBaseValidator
{
    public const string ERROR_CODE = 'update_hydration_entry_invalid';

    /**
     * @param bool $isFromToday whether the entry falls on the day in progress
     *
     * @throws ValidationException
     */
    public function validate(UpdateHydrationEntryDataInput $input, bool $isFromToday): void
    {
        $violations = $this->getViolations($input);
        $violations = EntryFromTodayConstraint::validate($isFromToday, $violations);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
