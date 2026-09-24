<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Provider;

use App\Domain\DTO\DataModel\HabitDataModel;
use App\Domain\DTO\DataModel\HabitSubscriptionDataModel;
use App\Domain\DTO\DataModel\UserDataModel;

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
     * The person's active subscriptions fed by one tracker — the ones a tracker write re-evaluates.
     *
     * @return list<HabitSubscriptionDataModel>
     */
    public function findActiveForOwnerAndTracker(UserDataModel $owner, string $trackerKind): array;
}
