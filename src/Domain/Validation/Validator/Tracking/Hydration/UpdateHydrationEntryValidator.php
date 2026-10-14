<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Tracking\Hydration;

use App\Domain\DTO\DataModel\Tracking\HydrationEntryDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Tracking\Hydration\UpdateHydrationEntryDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Domain\Validation\Constraint\Tracking\Hydration\EntryFromTodayConstraint;
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
    public function validate(UserDataModel $currentUser, HydrationEntryDataModel $entry, UpdateHydrationEntryDataInput $input, bool $isFromToday): void
    {
        $access = IsOwnerConstraint::validate($currentUser, $entry->hydrationDay->owner);
        if ([] !== $access) {
            throw new ValidationException(self::ERROR_CODE, $access);
        }

        $violations = $this->getViolations($input);
        $violations = EntryFromTodayConstraint::validate($isFromToday, $violations);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
