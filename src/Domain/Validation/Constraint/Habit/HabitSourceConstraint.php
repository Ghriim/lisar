<?php

declare(strict_types=1);

namespace App\Domain\Validation\Constraint\Habit;

use App\Domain\Registry\Habit\HabitSourceRegistry;
use App\Domain\Registry\Habit\HabitTrackerRegistry;

use function in_array;

/**
 * A tracker habit must name a tracker the application ships and a mark to cross; a manual one
 * needs neither. Only the tracker case carries a rule — a manual habit's tracker fields are simply
 * ignored, and the use case clears them.
 */
final readonly class HabitSourceConstraint
{
    public const string TRACKER_KIND_UNKNOWN = 'tracker_kind_unknown';
    public const string TRACKER_THRESHOLD_REQUIRED = 'tracker_threshold_required';

    /**
     * @param array<string, list<string>> $violations
     *
     * @return array<string, list<string>>
     */
    public static function validate(string $sourceKind, ?string $trackerKind, ?int $trackerThreshold, array $violations = []): array
    {
        if (HabitSourceRegistry::TRACKER !== $sourceKind) {
            return $violations;
        }

        if (null === $trackerKind || false === in_array($trackerKind, HabitTrackerRegistry::ALL, true)) {
            $violations['trackerKind'][] = self::TRACKER_KIND_UNKNOWN;
        }

        if (null === $trackerThreshold) {
            $violations['trackerThreshold'][] = self::TRACKER_THRESHOLD_REQUIRED;
        }

        return $violations;
    }
}
