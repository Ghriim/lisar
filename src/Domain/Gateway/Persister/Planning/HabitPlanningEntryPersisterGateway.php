<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Persister\Planning;

use App\Domain\DTO\DataModel\Habits\HabitSubscriptionDataModel;
use App\Domain\DTO\DataModel\Planning\HabitPlanningEntryDataModel;

interface HabitPlanningEntryPersisterGateway
{
    public function create(HabitPlanningEntryDataModel $entry): HabitPlanningEntryDataModel;

    public function update(HabitPlanningEntryDataModel $entry): HabitPlanningEntryDataModel;

    public function delete(HabitPlanningEntryDataModel $entry): void;

    /** Every entry of that subscription, in one statement. */
    public function deleteAllForSubscription(HabitSubscriptionDataModel $subscription): void;
}
