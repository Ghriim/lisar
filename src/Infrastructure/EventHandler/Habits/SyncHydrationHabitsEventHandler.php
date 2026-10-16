<?php

declare(strict_types=1);

namespace App\Infrastructure\EventHandler\Habits;

use App\Domain\DTO\Event\EventInterface;
use App\Domain\DTO\Event\Tracking\HydrationEntryCreatedEvent;
use App\Domain\DTO\Event\Tracking\HydrationEntryDeletedEvent;
use App\Domain\DTO\Event\Tracking\HydrationEntryUpdatedEvent;
use App\Domain\Factory\DataModelFactory\Habits\HabitEntryDataModelFactory;
use App\Domain\Gateway\Persister\Habits\HabitEntryPersisterGateway;
use App\Domain\Gateway\Provider\Habits\HabitSubscriptionProviderGateway;
use App\Domain\Registry\Habits\HabitTrackerRegistry;
use App\Domain\Tracking\DayClock;
use LogicException;

/** A drink logged, corrected or removed moves its day's total: the hydration habits follow. */
final readonly class SyncHydrationHabitsEventHandler extends AbstractTrackerHabitsEventHandler
{
    public function __construct(
        HabitSubscriptionProviderGateway $habitSubscriptionProviderGateway,
        HabitEntryPersisterGateway $habitEntryPersisterGateway,
        HabitEntryDataModelFactory $habitEntryDataModelFactory,
        DayClock $clock,
    ) {
        parent::__construct($habitSubscriptionProviderGateway, $habitEntryPersisterGateway, $habitEntryDataModelFactory, $clock);
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

        // The persister keeps the day's entries in step with the write, so the total is read off
        // the day the event carries rather than off a second read of it.
        $this->keep($day->owner, HabitTrackerRegistry::HYDRATION, $day->getTotalInMillilitres(), $day->day);
    }
}
