<?php

declare(strict_types=1);

namespace App\Infrastructure\EventHandler\Habits;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Factory\DataModelFactory\Habits\HabitEntryDataModelFactory;
use App\Domain\Gateway\Persister\Habits\HabitEntryPersisterGateway;
use App\Domain\Gateway\Provider\Habits\HabitEntryProviderGateway;
use App\Domain\Gateway\Provider\Habits\HabitSubscriptionProviderGateway;
use App\Domain\Tracking\DayClock;
use App\Infrastructure\EventHandler\EventHandlerInterface;
use DateTimeImmutable;

/**
 * What every tracker's habits handler shares: once a handler has worked out its tracker's figure
 * for a day, every active subscription watching that tracker is kept, or unkept, to match.
 *
 * The coupling runs one way. A tracker knows nothing of habits: it writes, its persister says so,
 * and the handler of that tracker reads the figure off what was written.
 */
abstract readonly class AbstractTrackerHabitsEventHandler implements EventHandlerInterface
{
    public function __construct(
        private HabitSubscriptionProviderGateway $habitSubscriptionProviderGateway,
        private HabitEntryProviderGateway $habitEntryProviderGateway,
        private HabitEntryPersisterGateway $habitEntryPersisterGateway,
        private HabitEntryDataModelFactory $habitEntryDataModelFactory,
        protected DayClock $clock,
    ) {
    }

    /** "The owner's figure for `$trackerKind` is now `$value` on `$day`." */
    protected function keep(UserDataModel $owner, string $trackerKind, int $value, DateTimeImmutable $day): void
    {
        foreach ($this->habitSubscriptionProviderGateway->findActiveForOwnerAndTracker($owner, $trackerKind) as $subscription) {
            $existing = $this->habitEntryProviderGateway->findOneForSubscriptionAndDay($subscription, $day);
            $entry = $this->habitEntryDataModelFactory->buildTrackerEntry($subscription, $existing, $trackerKind, $value, $day, $this->clock->now());

            if (null === $entry) {
                continue;
            }

            if (null === $existing) {
                $this->habitEntryPersisterGateway->create($entry);
            } else {
                $this->habitEntryPersisterGateway->update($entry);
            }
        }
    }
}
