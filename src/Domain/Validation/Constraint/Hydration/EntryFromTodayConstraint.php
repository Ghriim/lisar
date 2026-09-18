<?php

declare(strict_types=1);

namespace App\Domain\Validation\Constraint\Hydration;

/**
 * Only the day in progress can be written to. What was missed is missed: correcting the past
 * would make a tracker into a diary, and the point of this one is what it says about now.
 */
final readonly class EntryFromTodayConstraint
{
    public const string ENTRY_NOT_FROM_TODAY = 'entry_not_from_today';

    /**
     * @param bool                        $isFromToday whether the entry falls on the day in progress
     * @param array<string, list<string>> $violations
     *
     * @return array<string, list<string>>
     */
    public static function validate(bool $isFromToday, array $violations = []): array
    {
        if (false === $isFromToday) {
            $violations['id'][] = self::ENTRY_NOT_FROM_TODAY;
        }

        return $violations;
    }
}
