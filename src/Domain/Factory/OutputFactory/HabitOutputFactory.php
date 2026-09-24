<?php

declare(strict_types=1);

namespace App\Domain\Factory\OutputFactory;

use App\Domain\DTO\DataModel\HabitEntryDataModel;
use App\Domain\DTO\DataModel\HabitSubscriptionDataModel;
use App\Domain\DTO\Output\Habit\HabitDataOutput;
use App\Domain\DTO\Output\Habit\HabitDayDataOutput;
use DateTimeImmutable;

use function sprintf;

/**
 * A subscribed habit's line: its name and icon from the subscription's habit, and the last seven
 * days drawn from that subscription's entries. A day with no entry, or an entry not kept, is an
 * empty circle; a kept one is a checked circle.
 */
final readonly class HabitOutputFactory
{
    /** The window the panel draws: today and the six days before it. */
    public const int WINDOW_IN_DAYS = 7;

    /**
     * @param list<HabitSubscriptionDataModel> $subscriptions
     * @param list<HabitEntryDataModel>        $entries       every entry of those subscriptions since the window opened
     *
     * @return list<HabitDataOutput>
     */
    public function buildMany(array $subscriptions, array $entries, DateTimeImmutable $today): array
    {
        $completed = $this->completionBySubscription($entries);

        $outputs = [];
        foreach ($subscriptions as $subscription) {
            $outputs[] = $this->build($subscription, $completed[$subscription->id] ?? [], $today);
        }

        return $outputs;
    }

    /**
     * @param list<HabitEntryDataModel> $entries this subscription's entries since the window opened
     */
    public function buildOne(HabitSubscriptionDataModel $subscription, array $entries, DateTimeImmutable $today): HabitDataOutput
    {
        return $this->build($subscription, $this->completionBySubscription($entries)[$subscription->id] ?? [], $today);
    }

    /**
     * @param array<string, bool> $completedByDay day (YYYY-MM-DD) => kept
     */
    private function build(HabitSubscriptionDataModel $subscription, array $completedByDay, DateTimeImmutable $today): HabitDataOutput
    {
        $days = [];
        $isCompletedToday = false;

        for ($offset = self::WINDOW_IN_DAYS - 1; $offset >= 0; --$offset) {
            $key = $today->modify(sprintf('-%d days', $offset))->format('Y-m-d');
            $kept = $completedByDay[$key] ?? false;

            $mark = new HabitDayDataOutput();
            $mark->day = $key;
            $mark->isCompleted = $kept;
            $days[] = $mark;

            if (0 === $offset) {
                $isCompletedToday = $kept;
            }
        }

        $output = new HabitDataOutput();
        $output->habitId = $subscription->habit->id ?? 0;
        $output->name = $subscription->habit->name;
        $output->icon = $subscription->habit->icon;
        $output->sourceKind = $subscription->habit->sourceKind;
        $output->days = $days;
        $output->isCompletedToday = $isCompletedToday;

        return $output;
    }

    /**
     * @param list<HabitEntryDataModel> $entries
     *
     * @return array<int, array<string, bool>> subscription id => (day => kept)
     */
    private function completionBySubscription(array $entries): array
    {
        $map = [];
        foreach ($entries as $entry) {
            $map[$entry->subscription->id ?? 0][$entry->day->format('Y-m-d')] = $entry->isCompleted;
        }

        return $map;
    }
}
