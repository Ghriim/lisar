<?php

declare(strict_types=1);

namespace App\Domain\Validation\Constraint\User;

use App\Domain\DTO\DataModel\UserDataModel;

final readonly class UsernameAvailableConstraint
{
    public const string USERNAME_ALREADY_USED = 'username_already_used';

    /**
     * @param UserDataModel|null          $existingUser the account already holding that username, if any
     * @param array<string, list<string>> $violations
     *
     * @return array<string, list<string>>
     */
    public static function validate(?UserDataModel $existingUser, array $violations = []): array
    {
        if (null !== $existingUser) {
            $violations['username'][] = self::USERNAME_ALREADY_USED;
        }

        return $violations;
    }
}
