<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Tracking\Weight;

use App\Domain\DTO\DataModel\User\UserDataModel;

/**
 * What `GetLatestWeightUseCase` checks before it acts. Nothing yet: the next rule lands here, as a
 * Constraint.
 */
final readonly class GetLatestWeightValidator
{
    public function validate(UserDataModel $currentUser): void
    {
    }
}
