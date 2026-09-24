<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Provider;

use App\Domain\DTO\DataModel\HabitEntryDataModel;
use App\Domain\DTO\DataModel\HabitSubscriptionDataModel;
use App\Domain\DTO\DataModel\UserDataModel;
use DateTimeImmutable;

interface HabitEntryProviderGateway
{
    /** That subscription's day, or null when it was neither kept nor evaluated. */
    public function findOneForSubscriptionAndDay(HabitSubscriptionDataModel $subscription, DateTimeImmutable $day): ?HabitEntryDataModel;

    /**
     * Every entry from `$since` onward across the person's subscriptions — what the seven-day
     * marks are read from. The subscription is joined.
     *
     * @return list<HabitEntryDataModel>
     */
    public function findForOwnerSince(UserDataModel $owner, DateTimeImmutable $since): array;

    /**
     * One subscription's entries from `$since` onward — the marks a single-habit answer needs.
     *
     * @return list<HabitEntryDataModel>
     */
    public function findForSubscriptionSince(HabitSubscriptionDataModel $subscription, DateTimeImmutable $since): array;
}
