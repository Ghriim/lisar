<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Provider\Habits;

use App\Domain\DTO\Aggregate\Habits\HabitSubscriptionDay;
use App\Domain\DTO\DataModel\Habits\HabitDataModel;
use App\Domain\DTO\DataModel\Habits\HabitSubscriptionDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use DateTimeImmutable;

interface HabitSubscriptionProviderGateway
{
    /** The person's subscription to a habit, whatever its state — so a dropped one can be resumed. */
    public function findOneForOwnerAndHabit(UserDataModel $owner, HabitDataModel $habit): ?HabitSubscriptionDataModel;

    /**
     * The person's kept habits: their active subscriptions to still-active catalogue habits, by
     * habit name. The habit is joined, ready to read.
     *
     * @return list<HabitSubscriptionDataModel>
     */
    public function findActiveForOwner(UserDataModel $owner): array;

    /**
     * The person's active subscriptions fed by one tracker — the ones a tracker write re-evaluates —
     * each with its entry for that day, joined in the same query.
     *
     * @return list<HabitSubscriptionDay>
     */
    public function findActiveForOwnerAndTrackerWithEntryOn(UserDataModel $owner, string $trackerKind, DateTimeImmutable $day): array;
}
