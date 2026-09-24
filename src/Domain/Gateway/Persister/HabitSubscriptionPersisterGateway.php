<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Persister;

use App\Domain\DTO\DataModel\HabitSubscriptionDataModel;

/**
 * No `delete`: unsubscribing clears `isActive` rather than dropping the row, so the days already
 * kept are not erased with it.
 */
interface HabitSubscriptionPersisterGateway
{
    public function create(HabitSubscriptionDataModel $subscription): HabitSubscriptionDataModel;

    public function update(HabitSubscriptionDataModel $subscription): HabitSubscriptionDataModel;
}
