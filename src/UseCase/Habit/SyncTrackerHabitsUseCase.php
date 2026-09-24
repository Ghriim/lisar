<?php

declare(strict_types=1);

namespace App\UseCase\Habit;

use App\Domain\DTO\DataModel\HabitEntryDataModel;
use App\Domain\Gateway\Persister\HabitEntryPersisterGateway;
use App\Domain\Gateway\Provider\HabitEntryProviderGateway;
use App\Domain\Gateway\Provider\HabitSubscriptionProviderGateway;
use App\Domain\Gateway\Provider\UserProviderGateway;
use App\Domain\Tracking\DayClock;
use App\UseCase\UseCaseInterface;
use DateTimeImmutable;

/**
 * Re-evaluating the habits a tracker feeds, the moment that tracker is written. A tracker use case
 * calls this after it writes its day: "the owner's figure for `$trackerKind` is now `$value` on
 * `$day`" — and every active subscription watching that tracker is kept, or unkept, accordingly.
 *
 * A day that reaches the mark is kept, with `completedAt` stamped the instant it crossed; a figure
 * corrected back below its mark unkeeps the day again. Nothing is written for a subscription that
 * was never kept and still is not: an empty day is an absent row.
 *
 * The coupling runs one way. A tracker knows nothing of habits; it only announces its figure.
 */
final readonly class SyncTrackerHabitsUseCase implements UseCaseInterface
{
    public function __construct(
        private UserProviderGateway $userProviderGateway,
        private HabitSubscriptionProviderGateway $habitSubscriptionProviderGateway,
        private HabitEntryProviderGateway $habitEntryProviderGateway,
        private HabitEntryPersisterGateway $habitEntryPersisterGateway,
        private DayClock $clock,
    ) {
    }

    public function execute(int $ownerId, string $trackerKind, int $value, DateTimeImmutable $day): void
    {
        $owner = $this->userProviderGateway->findOneById($ownerId);
        if (null === $owner) {
            return;
        }

        $subscriptions = $this->habitSubscriptionProviderGateway->findActiveForOwnerAndTracker($owner, $trackerKind);

        foreach ($subscriptions as $subscription) {
            $threshold = $subscription->habit->trackerThreshold;
            $isKept = null !== $threshold && $value >= $threshold;

            $entry = $this->habitEntryProviderGateway->findOneForSubscriptionAndDay($subscription, $day);
            $isNew = null === $entry;

            // Nothing to record: the subscription was never kept today and still is not.
            if (true === $isNew && false === $isKept) {
                continue;
            }

            if (null === $entry) {
                $entry = new HabitEntryDataModel();
                $entry->subscription = $subscription;
                $entry->day = $day;
            }

            if (true === $isKept && false === $entry->isCompleted) {
                $entry->completedAt = $this->clock->now();
            }
            if (false === $isKept) {
                $entry->completedAt = null;
            }

            $entry->isCompleted = $isKept;
            $entry->source = $trackerKind;

            if (true === $isNew) {
                $this->habitEntryPersisterGateway->create($entry);
            } else {
                $this->habitEntryPersisterGateway->update($entry);
            }
        }
    }
}
