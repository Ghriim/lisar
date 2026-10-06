<?php

declare(strict_types=1);

namespace App\Domain\Validation\Constraint\Workout;

/**
 * Deleting a set type a logged set carries would rewrite what that set was. Deactivating retires
 * it and leaves the sets as they are.
 */
final readonly class SetTypeUnusedConstraint
{
    public const string IN_USE = 'set_type_in_use';

    /**
     * @param int                         $setCount   how many logged sets, anyone's, carry it
     * @param array<string, list<string>> $violations
     *
     * @return array<string, list<string>>
     */
    public static function validate(int $setCount, array $violations = []): array
    {
        if (0 < $setCount) {
            $violations['id'][] = self::IN_USE;
        }

        return $violations;
    }
}
