<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister\Habits;

use App\Domain\DTO\DataModel\Habits\HabitSubscriptionDataModel;
use App\Domain\Gateway\Persister\Habits\HabitSubscriptionPersisterGateway;
use App\Infrastructure\Persister\AbstractBaseMysqlPersister;

/**
 * @extends AbstractBaseMysqlPersister<HabitSubscriptionDataModel>
 */
final class HabitSubscriptionPersister extends AbstractBaseMysqlPersister implements HabitSubscriptionPersisterGateway
{
    public function create(HabitSubscriptionDataModel $subscription): HabitSubscriptionDataModel
    {
        return $this->persistAndStampCreate($subscription);
    }

    public function update(HabitSubscriptionDataModel $subscription): HabitSubscriptionDataModel
    {
        return $this->persistAndStampUpdate($subscription);
    }
}
