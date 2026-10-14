<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Habits;

use App\Domain\DTO\DataModel\User\UserDataModel;

/**
 * What `ListHabitCatalogUseCase` checks before it acts. Nothing yet: the next rule lands here, as a
 * Constraint.
 */
final readonly class ListHabitCatalogValidator
{
    public function validate(UserDataModel $currentUser): void
    {
    }
}
