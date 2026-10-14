<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Tracking\Hydration;

use App\Domain\DTO\DataModel\Tracking\HydrationEntryDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Domain\Validation\Constraint\Tracking\Hydration\EntryFromTodayConstraint;

/**
 * `$isFromToday` says whether the entry belongs to the day in progress.
 */
final readonly class DeleteHydrationEntryValidator
{
    public const string ERROR_CODE = 'delete_hydration_entry_invalid';

    /**
     * @throws ValidationException
     */
    public function validate(UserDataModel $currentUser, HydrationEntryDataModel $entry, bool $isFromToday): void
    {
        $access = IsOwnerConstraint::validate($currentUser, $entry->hydrationDay->owner);
        if ([] !== $access) {
            throw new ValidationException(self::ERROR_CODE, $access);
        }

        $violations = [];
        $violations = EntryFromTodayConstraint::validate($isFromToday, $violations);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
