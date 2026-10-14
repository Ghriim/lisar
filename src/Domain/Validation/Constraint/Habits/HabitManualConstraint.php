<?php

declare(strict_types=1);

namespace App\Domain\Validation\Constraint\Habits;

use App\Domain\DTO\DataModel\Habits\HabitDataModel;
use App\Domain\Registry\Habits\HabitSourceRegistry;

/** A habit a person ticks by hand. A tracker habit is kept by its tracker, never by a tap. */
final readonly class HabitManualConstraint
{
    public const string NOT_MANUAL = 'habit_not_manual';

    /**
     * @param array<string, list<string>> $violations
     *
     * @return array<string, list<string>>
     */
    public static function validate(HabitDataModel $habit, array $violations = []): array
    {
        if (HabitSourceRegistry::MANUAL !== $habit->sourceKind) {
            $violations['habit'][] = self::NOT_MANUAL;
        }

        return $violations;
    }
}
