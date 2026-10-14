<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\User;

use App\Domain\DTO\DataModel\User\UserDataModel;

/**
 * What `GetUserUseCase` checks before it acts. Nothing yet: the next rule lands here, as a
 * Constraint.
 */
final readonly class GetUserValidator
{
    public function validate(UserDataModel $currentUser): void
    {
    }
}
