<?php

declare(strict_types=1);

namespace App\Infrastructure\EventHandler\Habits;

use App\Domain\DTO\DataModel\Tracking\HydrationDayDataModel;
use App\Domain\DTO\Event\EventInterface;
use App\Domain\DTO\Event\Tracking\HydrationEntryCreatedEvent;
use App\Domain\DTO\Event\Tracking\HydrationEntryDeletedEvent;
use App\Domain\DTO\Event\Tracking\HydrationEntryUpdatedEvent;
use App\Domain\Factory\DataModelFactory\Habits\HabitEntryDataModelFactory;
use App\Domain\Gateway\Persister\Habits\HabitEntryPersisterGateway;
use App\Domain\Gateway\Provider\Habits\HabitEntryProviderGateway;
use App\Domain\Gateway\Provider\Habits\HabitSubscriptionProviderGateway;
use App\Domain\Gateway\Provider\Tracking\Hydration\HydrationDayProviderGateway;
use App\Domain\Registry\Habits\HabitTrackerRegistry;
use App\Domain\Tracking\DayClock;
use LogicException;

/** A drink logged, corrected or removed moves its day's total: the hydration habits follow. */
final readonly class SyncHydrationHabitsEventHandler extends AbstractTrackerHabitsEventHandler
{
    public function __construct(
        HabitSubscriptionProviderGateway $habitSubscriptionProviderGateway,
        HabitEntryProviderGateway $habitEntryProviderGateway,
        HabitEntryPersisterGateway $habitEntryPersisterGateway,
        HabitEntryDataModelFactory $habitEntryDataModelFactory,
        DayClock $clock,
        private HydrationDayProviderGateway $hydrationDayProviderGateway,
    ) {
        parent::__construct($habitSubscriptionProviderGateway, $habitEntryProviderGateway, $habitEntryPersisterGateway, $habitEntryDataModelFactory, $clock);
    }

    public static function getSupportedEvents(): array
    {
        return [HydrationEntryCreatedEvent::class, HydrationEntryUpdatedEvent::class, HydrationEntryDeletedEvent::class];
    }

    public function handle(EventInterface $event): void
    {
        $day = match (true) {
            $event instanceof HydrationEntryCreatedEvent, $event instanceof HydrationEntryUpdatedEvent => $event->entry->hydrationDay,
            $event instanceof HydrationEntryDeletedEvent => $event->day,
            default => throw new LogicException(sprintf('%s does not handle %s.', self::class, $event::class)),
        };

        $this->keep($day->owner, HabitTrackerRegistry::HYDRATION, $this->totalOf($day), $day->day);
    }

    /** Re-read with its entries, so the total counts every one of them, not just the one in hand. */
    private function totalOf(HydrationDayDataModel $day): int
    {
        return $this->hydrationDayProviderGateway->findOneForOwnerAndDay($day->owner, $day->day)?->getTotalInMillilitres() ?? 0;
    }
}
