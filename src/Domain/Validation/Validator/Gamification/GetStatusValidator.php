<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Gamification;

use App\Domain\DTO\DataModel\User\UserDataModel;

/**
 * What `GetStatusUseCase` checks before it acts. Nothing yet: everyone reads their own status, and
 * the next rule lands here, as a Constraint.
 */
final readonly class GetStatusValidator
{
    public function validate(UserDataModel $currentUser): void
    {
    }
}
