<?php

declare(strict_types=1);

namespace App\Infrastructure\EventHandler\Planning;

use App\Domain\DTO\Event\EventInterface;
use App\Domain\DTO\Event\Habits\HabitSubscriptionCreatedEvent;
use App\Domain\DTO\Event\Habits\HabitSubscriptionUpdatedEvent;
use App\Domain\Factory\DataModelFactory\Planning\HabitPlanningEntryDataModelFactory;
use App\Domain\Gateway\Persister\Planning\HabitPlanningEntryPersisterGateway;
use App\Domain\Tracking\DayClock;
use App\Infrastructure\EventHandler\EventHandlerInterface;
use LogicException;

/**
 * A habit is never without a planning: taken on, or taken back up after a drop — which took its
 * entries with it — it is planned every day from today, until the person says otherwise.
 */
final readonly class CreateDefaultHabitPlanningEntryEventHandler implements EventHandlerInterface
{
    public function __construct(
        private HabitPlanningEntryDataModelFactory $entryFactory,
        private HabitPlanningEntryPersisterGateway $entryPersisterGateway,
        private DayClock $clock,
    ) {
    }

    public static function getSupportedEvents(): array
    {
        return [HabitSubscriptionCreatedEvent::class, HabitSubscriptionUpdatedEvent::class];
    }

    public function handle(EventInterface $event): void
    {
        if (false === $event instanceof HabitSubscriptionCreatedEvent && false === $event instanceof HabitSubscriptionUpdatedEvent) {
            throw new LogicException(sprintf('%s does not handle %s.', self::class, $event::class));
        }

        if ($event instanceof HabitSubscriptionUpdatedEvent && false === $event->activates) {
            return;
        }

        $this->entryPersisterGateway->create($this->entryFactory->buildEveryDay($event->subscription, $this->clock->today()));
    }
}
