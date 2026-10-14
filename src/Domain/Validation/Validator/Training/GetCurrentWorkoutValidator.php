<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Training;

use App\Domain\DTO\DataModel\User\UserDataModel;

/**
 * What `GetCurrentWorkoutUseCase` checks before it acts. Nothing yet: the next rule lands here, as a
 * Constraint.
 */
final readonly class GetCurrentWorkoutValidator
{
    public function validate(UserDataModel $currentUser): void
    {
    }
}
