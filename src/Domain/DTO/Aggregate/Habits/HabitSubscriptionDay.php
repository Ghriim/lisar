<?php

declare(strict_types=1);

namespace App\Domain\DTO\Aggregate\Habits;

use App\Domain\DTO\DataModel\Habits\HabitEntryDataModel;
use App\Domain\DTO\DataModel\Habits\HabitSubscriptionDataModel;

/** A subscription with its entry for one day, read in the same query; null when the day has none yet. */
final readonly class HabitSubscriptionDay
{
    public function __construct(
        public HabitSubscriptionDataModel $subscription,
        public ?HabitEntryDataModel $entry,
    ) {
    }
}
