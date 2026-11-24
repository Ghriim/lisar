<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Planning;

use App\Domain\DTO\DataModel\Planning\TaskPlanningEntryDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;

final readonly class DeleteTaskPlanningEntryValidator
{
    public const string ERROR_CODE = 'delete_task_planning_entry_invalid';

    /**
     * @throws ValidationException
     */
    public function validate(UserDataModel $currentUser, TaskPlanningEntryDataModel $entry): void
    {
        $access = IsOwnerConstraint::validate($currentUser, $entry->owner);
        if ([] !== $access) {
            throw new ValidationException(self::ERROR_CODE, $access);
        }
    }
}
