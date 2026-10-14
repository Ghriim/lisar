<?php

declare(strict_types=1);

namespace App\Domain\Validation\Constraint\Common;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Registry\User\UserRoleRegistry;

/**
 * The caller is an administrator. The first rule of every back-office validator, thrown on alone,
 * with the same code as IsOwnerConstraint: the exception listener answers both with a 401.
 */
final readonly class IsAdminConstraint
{
    public const string NOT_ALLOWED = IsOwnerConstraint::NOT_ALLOWED;

    /**
     * @param array<string, list<string>> $violations
     *
     * @return array<string, list<string>>
     */
    public static function validate(UserDataModel $currentUser, array $violations = []): array
    {
        if (UserRoleRegistry::ADMIN !== $currentUser->role) {
            $violations['currentUser'][] = self::NOT_ALLOWED;
        }

        return $violations;
    }
}
