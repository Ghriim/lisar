<?php

declare(strict_types=1);

namespace App\Domain\Validation\Constraint\Common;

use App\Domain\DTO\DataModel\User\UserDataModel;

/**
 * The caller is the owner of what they act on — the data model itself, or the parent that carries
 * its owner (a drink's day) — and an active account. A validator calls it first and throws on it
 * alone: nothing else about what is not theirs is said back. The exception listener answers it
 * with a 401.
 */
final readonly class IsOwnerConstraint
{
    public const string NOT_ALLOWED = 'not_allowed';

    /**
     * @param array<string, list<string>> $violations
     *
     * @return array<string, list<string>>
     */
    public static function validate(UserDataModel $currentUser, UserDataModel $owner, array $violations = []): array
    {
        if (false === $currentUser->isActive || $owner->id !== $currentUser->id) {
            $violations['currentUser'][] = self::NOT_ALLOWED;
        }

        return $violations;
    }
}
