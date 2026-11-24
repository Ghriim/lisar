<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Provider\Planning;

use App\Domain\DTO\DataModel\Habits\HabitSubscriptionDataModel;
use App\Domain\DTO\DataModel\Planning\HabitPlanningEntryDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;

interface HabitPlanningEntryProviderGateway
{
    /** An entry by its id alone, with its subscription and habit: whose it is is the validator's to check. */
    public function findOneById(int $id): ?HabitPlanningEntryDataModel;

    /**
     * Every entry of the owner's active subscriptions to still-active habits, with the subscription
     * and its habit, the earliest start time first — those within the day before — then by id. Not
     * bounded by dates: a person plans a handful.
     *
     * @return list<HabitPlanningEntryDataModel>
     */
    public function findAllForOwner(UserDataModel $owner): array;

    /**
     * Every entry of one subscription — the days a single habit's line needs.
     *
     * @return list<HabitPlanningEntryDataModel>
     */
    public function findForSubscription(HabitSubscriptionDataModel $subscription): array;
}
