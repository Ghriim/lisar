<?php

declare(strict_types=1);

namespace App\Domain\Factory\OutputFactory\Planning;

use App\Domain\DataTransformer\DateDataTransformer;
use App\Domain\DTO\DataModel\Habits\HabitSubscriptionDataModel;
use App\Domain\DTO\DataModel\Planning\HabitPlanningEntryDataModel;
use App\Domain\DTO\Output\Planning\HabitPlanningDataOutput;
use App\Domain\DTO\Output\Planning\HabitPlanningEntryDataOutput;
use App\Domain\DTO\Output\Planning\HabitPlanningSlotDataOutput;
use DateTimeImmutable;

final readonly class HabitPlanningOutputFactory
{
    public function __construct(
        private PlanningScheduleOutputFactory $scheduleOutputFactory,
    ) {
    }

    /**
     * @param list<HabitPlanningEntryDataModel>                                                                                                     $entries
     * @param list<array{entry: HabitPlanningEntryDataModel|null, subscription: HabitSubscriptionDataModel, day: DateTimeImmutable, state: string}> $slots
     */
    public function buildMany(array $entries, array $slots): HabitPlanningDataOutput
    {
        $output = new HabitPlanningDataOutput();
        $output->entries = array_map($this->buildOne(...), $entries);

        foreach ($slots as $slot) {
            $one = new HabitPlanningSlotDataOutput();
            $one->entryId = $slot['entry']?->id;
            $one->habitId = (int) $slot['subscription']->habit->id;
            $one->day = (string) DateDataTransformer::dateToDayString($slot['day']);
            $one->state = $slot['state'];
            $output->slots[] = $one;
        }

        return $output;
    }

    public function buildOne(HabitPlanningEntryDataModel $entry): HabitPlanningEntryDataOutput
    {
        $output = new HabitPlanningEntryDataOutput();
        $output->id = (int) $entry->id;
        $output->habitId = (int) $entry->subscription->habit->id;
        $output->habitName = $entry->subscription->habit->name;
        $output->habitIcon = $entry->subscription->habit->icon;
        $output->schedule = $this->scheduleOutputFactory->buildOne($entry);

        return $output;
    }
}
