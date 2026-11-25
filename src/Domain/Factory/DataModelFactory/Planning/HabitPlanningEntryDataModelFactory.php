<?php

declare(strict_types=1);

namespace App\Domain\Factory\DataModelFactory\Planning;

use App\Domain\DataTransformer\DateDataTransformer;
use App\Domain\DTO\DataModel\Habits\HabitSubscriptionDataModel;
use App\Domain\DTO\DataModel\Planning\HabitPlanningEntryDataModel;
use App\Domain\DTO\Input\Planning\PlanningScheduleDataInput;
use App\Domain\Registry\Planning\PlanningRuleRegistry;
use DateTimeImmutable;

final readonly class HabitPlanningEntryDataModelFactory
{
    /** Monday to Sunday: « every day » is a weekly rule falling on all seven. */
    public const array EVERY_WEEKDAY = [1, 2, 3, 4, 5, 6, 7];

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

    /**
     * The entry a habit starts with when it is taken on: every day, from that day on. A habit is
     * never without a planning — its entries say which days it counts, and this is the first.
     */
    public function buildEveryDay(HabitSubscriptionDataModel $subscription, DateTimeImmutable $today): HabitPlanningEntryDataModel
    {
        return $this->build($subscription, new PlanningScheduleDataInput(
            rule: PlanningRuleRegistry::WEEKLY,
            startDate: (string) DateDataTransformer::dateToDayString($today),
            weekdays: self::EVERY_WEEKDAY,
        ));
    }

    /** The entry replaced whole by what was sent. */
    public function apply(HabitPlanningEntryDataModel $entry, HabitSubscriptionDataModel $subscription, PlanningScheduleDataInput $schedule): void
    {
        $entry->subscription = $subscription;
        $this->planningEntryFactory->applySchedule($entry, $schedule);
    }
}
