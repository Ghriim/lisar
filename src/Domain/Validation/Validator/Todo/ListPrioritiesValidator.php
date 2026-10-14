<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Todo;

use App\Domain\DTO\DataModel\User\UserDataModel;

/**
 * What `ListPrioritiesUseCase` checks before it acts. Nothing yet: the next rule lands here, as a
 * Constraint.
 */
final readonly class ListPrioritiesValidator
{
    public function validate(UserDataModel $currentUser): void
    {
    }
}
