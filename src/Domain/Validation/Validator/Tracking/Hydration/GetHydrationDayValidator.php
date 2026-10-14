<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Tracking\Hydration;

use App\Domain\DTO\DataModel\User\UserDataModel;

/**
 * What `GetHydrationDayUseCase` checks before it acts. Nothing yet: the next rule lands here, as a
 * Constraint.
 */
final readonly class GetHydrationDayValidator
{
    public function validate(UserDataModel $currentUser): void
    {
    }
}
