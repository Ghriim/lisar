<?php

declare(strict_types=1);

namespace App\Domain\Factory\DataModelFactory\Planning;

use App\Domain\DTO\DataModel\Habits\HabitSubscriptionDataModel;
use App\Domain\DTO\DataModel\Planning\HabitPlanningEntryDataModel;
use App\Domain\DTO\Input\Planning\PlanningScheduleDataInput;

final readonly class HabitPlanningEntryDataModelFactory
{
    public function __construct(
        private PlanningEntryDataModelFactory $planningEntryFactory,
    ) {
    }

    /** A new entry of the subscription's owner, saying which days `$subscription` counts. */
    public function build(HabitSubscriptionDataModel $subscription, PlanningScheduleDataInput $schedule): HabitPlanningEntryDataModel
    {
        $entry = new HabitPlanningEntryDataModel();
        $entry->owner = $subscription->owner;
        $this->apply($entry, $subscription, $schedule);

        return $entry;
    }

    /** The entry replaced whole by what was sent. */
    public function apply(HabitPlanningEntryDataModel $entry, HabitSubscriptionDataModel $subscription, PlanningScheduleDataInput $schedule): void
    {
        $entry->subscription = $subscription;
        $this->planningEntryFactory->applySchedule($entry, $schedule);
    }
}
