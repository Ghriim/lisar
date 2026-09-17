<?php

declare(strict_types=1);

namespace App\Domain\Validation\Constraint\User;

/**
 * An administrator must not deactivate their own account: they would lock themselves out of the
 * back-office, and only another administrator — or a console command — could undo it.
 */
final readonly class SelfDeactivationConstraint
{
    public const string CANNOT_DEACTIVATE_YOURSELF = 'cannot_deactivate_yourself';

    /**
     * @param array<string, list<string>> $violations
     *
     * @return array<string, list<string>>
     */
    public static function validate(int $targetUserId, int $actingUserId, array $violations = []): array
    {
        if ($targetUserId === $actingUserId) {
            $violations['id'][] = self::CANNOT_DEACTIVATE_YOURSELF;
        }

        return $violations;
    }
}
