<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Provider\Habits;

use App\Domain\DTO\DataModel\Habits\HabitEntryDataModel;
use App\Domain\DTO\DataModel\Habits\HabitSubscriptionDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use DateTimeImmutable;

interface HabitEntryProviderGateway
{
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
