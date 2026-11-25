<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Planning;

use App\Domain\DTO\DataModel\Planning\HabitPlanningEntryDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Domain\Validation\Constraint\Planning\HabitPlanningEntryNotLastConstraint;

final readonly class DeleteHabitPlanningEntryValidator
{
    public const string ERROR_CODE = 'delete_habit_planning_entry_invalid';

    /**
     * @param list<HabitPlanningEntryDataModel> $subscriptionEntries every entry of the entry's habit, that one included
     *
     * @throws ValidationException
     */
    public function validate(UserDataModel $currentUser, HabitPlanningEntryDataModel $entry, array $subscriptionEntries): void
    {
        $access = IsOwnerConstraint::validate($currentUser, $entry->owner);
        if ([] !== $access) {
            throw new ValidationException(self::ERROR_CODE, $access);
        }

        $violations = HabitPlanningEntryNotLastConstraint::validate($subscriptionEntries);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
