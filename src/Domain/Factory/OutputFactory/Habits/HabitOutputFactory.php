<?php

declare(strict_types=1);

namespace App\Domain\Factory\OutputFactory\Habits;

use App\Domain\DTO\DataModel\Habits\HabitEntryDataModel;
use App\Domain\DTO\DataModel\Habits\HabitSubscriptionDataModel;
use App\Domain\DTO\Output\Habits\HabitDataOutput;
use App\Domain\DTO\Output\Habits\HabitDayDataOutput;
use App\Domain\Factory\OutputFactory\Category\CategoryOutputFactory;
use DateTimeImmutable;

use function sprintf;

/**
 * A subscribed habit's line: its name and icon from the subscription's habit, and the last seven
 * days drawn from that subscription's entries. A day with no entry, or an entry not kept, is an
 * empty circle; a kept one is a checked circle. A planned habit's days its planning does not ask
 * for are marked so, to be drawn dimmed.
 */
final readonly class HabitOutputFactory
{
    /** The window the panel draws: today and the six days before it. */
    public const int WINDOW_IN_DAYS = 7;

    public function __construct(private CategoryOutputFactory $categoryOutputFactory)
    {
    }

    /**
     * @param list<HabitSubscriptionDataModel> $subscriptions
     * @param list<HabitEntryDataModel>        $entries       every entry of those subscriptions since the window opened
     * @param array<int, array<string, true>>  $plannedDays   subscription id => its planned days over the window; a subscription absent counts every day
     *
     * @return list<HabitDataOutput>
     */
    public function buildMany(array $subscriptions, array $entries, array $plannedDays, DateTimeImmutable $today): array
    {
        $completed = $this->completionBySubscription($entries);

        $outputs = [];
        foreach ($subscriptions as $subscription) {
            $outputs[] = $this->build($subscription, $completed[$subscription->id] ?? [], $plannedDays[$subscription->id] ?? null, $today);
        }

        return $outputs;
    }

    /**
     * @param list<HabitEntryDataModel> $entries     this subscription's entries since the window opened
     * @param array<string, true>|null  $plannedDays its planned days over the window; null when it counts every day
     */
    public function buildOne(HabitSubscriptionDataModel $subscription, array $entries, ?array $plannedDays, DateTimeImmutable $today): HabitDataOutput
    {
        return $this->build($subscription, $this->completionBySubscription($entries)[$subscription->id] ?? [], $plannedDays, $today);
    }

    /**
     * @param array<string, bool>      $completedByDay day (YYYY-MM-DD) => kept
     * @param array<string, true>|null $plannedDays    null when it counts every day
     */
    private function build(HabitSubscriptionDataModel $subscription, array $completedByDay, ?array $plannedDays, DateTimeImmutable $today): HabitDataOutput
    {
        $days = [];
        $isCompletedToday = false;

        for ($offset = self::WINDOW_IN_DAYS - 1; $offset >= 0; --$offset) {
            $key = $today->modify(sprintf('-%d days', $offset))->format('Y-m-d');
            $kept = $completedByDay[$key] ?? false;

            $mark = new HabitDayDataOutput();
            $mark->day = $key;
            $mark->isCompleted = $kept;
            $mark->isPlanned = null === $plannedDays || isset($plannedDays[$key]);
            $days[] = $mark;

            if (0 === $offset) {
                $isCompletedToday = $kept;
            }
        }

        $output = new HabitDataOutput();
        $output->habitId = $subscription->habit->id ?? 0;
        $output->name = $subscription->habit->name;
        $output->sourceKind = $subscription->habit->sourceKind;
        $output->days = $days;
        $output->category = null === $subscription->habit->category
            ? null
            : $this->categoryOutputFactory->buildOne($subscription->habit->category);
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
