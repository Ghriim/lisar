<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Tracking\Sleep;

use App\Domain\DTO\DataModel\User\UserDataModel;

/**
 * What `GetSleepNightUseCase` checks before it acts. Nothing yet: the next rule lands here, as a
 * Constraint.
 */
final readonly class GetSleepNightValidator
{
    public function validate(UserDataModel $currentUser): void
    {
    }
}
