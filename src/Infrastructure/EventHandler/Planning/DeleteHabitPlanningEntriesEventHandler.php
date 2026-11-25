<?php

declare(strict_types=1);

namespace App\Infrastructure\EventHandler\Planning;

use App\Domain\DTO\Event\EventInterface;
use App\Domain\DTO\Event\Habits\HabitSubscriptionUpdatedEvent;
use App\Domain\Gateway\Persister\Planning\HabitPlanningEntryPersisterGateway;
use App\Infrastructure\EventHandler\EventHandlerInterface;
use LogicException;

/**
 * Dropping a habit takes its planning entries with it: taken back up, it starts again from every
 * day (CreateDefaultHabitPlanningEntryEventHandler). The days already kept stay — they hang off the subscription, which is
 * deactivated, not deleted.
 */
final readonly class DeleteHabitPlanningEntriesEventHandler implements EventHandlerInterface
{
    public function __construct(
        private HabitPlanningEntryPersisterGateway $habitPlanningEntryPersisterGateway,
    ) {
    }

    public static function getSupportedEvents(): array
    {
        return [HabitSubscriptionUpdatedEvent::class];
    }

    public function handle(EventInterface $event): void
    {
        if (false === $event instanceof HabitSubscriptionUpdatedEvent) {
            throw new LogicException(sprintf('%s does not handle %s.', self::class, $event::class));
        }

        if (true === $event->deactivates) {
            $this->habitPlanningEntryPersisterGateway->deleteAllForSubscription($event->subscription);
        }
    }
}
