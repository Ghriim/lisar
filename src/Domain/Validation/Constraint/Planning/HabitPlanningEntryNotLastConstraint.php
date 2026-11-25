<?php

declare(strict_types=1);

namespace App\Domain\Validation\Constraint\Planning;

use App\Domain\DTO\DataModel\Planning\HabitPlanningEntryDataModel;

use function count;

/**
 * A kept habit always has a planning: the last of its entries cannot be deleted. It is changed, or
 * the habit is dropped — which takes its entries with it.
 */
final readonly class HabitPlanningEntryNotLastConstraint
{
    public const string LAST_HABIT_PLANNING_ENTRY = 'last_habit_planning_entry';

    /**
     * @param list<HabitPlanningEntryDataModel> $subscriptionEntries every entry of the habit the one deleted belongs to, that one included
     * @param array<string, list<string>>       $violations
     *
     * @return array<string, list<string>>
     */
    public static function validate(array $subscriptionEntries, array $violations = []): array
    {
        if (1 >= count($subscriptionEntries)) {
            $violations['id'][] = self::LAST_HABIT_PLANNING_ENTRY;
        }

        return $violations;
    }
}
