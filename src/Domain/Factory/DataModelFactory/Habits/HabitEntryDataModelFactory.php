<?php

declare(strict_types=1);

namespace App\Domain\Factory\DataModelFactory\Habits;

use App\Domain\DTO\DataModel\Habits\HabitEntryDataModel;
use App\Domain\DTO\DataModel\Habits\HabitSubscriptionDataModel;
use DateTimeImmutable;

final readonly class HabitEntryDataModelFactory
{
    /**
     * A tracker habit's day, as its tracker's figure now has it: kept when `$value` reaches the
     * habit's mark, with `completedAt` the instant it crossed; unkept again when a correction
     * brings it back below. Answers the entry to write — `$existing` brought up to date, or a new
     * one — or null when there is nothing to record: never kept that day, and still not.
     */
    public function buildTrackerEntry(
        HabitSubscriptionDataModel $subscription,
        ?HabitEntryDataModel $existing,
        string $trackerKind,
        int $value,
        DateTimeImmutable $day,
        DateTimeImmutable $now,
    ): ?HabitEntryDataModel {
        $threshold = $subscription->habit->trackerThreshold;
        $isKept = null !== $threshold && $value >= $threshold;

        if (null === $existing && false === $isKept) {
            return null;
        }

        $entry = $existing;
        if (null === $entry) {
            $entry = new HabitEntryDataModel();
            $entry->subscription = $subscription;
            $entry->day = $day;
        }

        if (true === $isKept && false === $entry->isCompleted) {
            $entry->completedAt = $now;
        }
        if (false === $isKept) {
            $entry->completedAt = null;
        }

        $entry->isCompleted = $isKept;
        $entry->source = $trackerKind;

        return $entry;
    }
}
