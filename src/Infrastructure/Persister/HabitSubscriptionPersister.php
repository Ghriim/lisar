<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister;

use App\Domain\DTO\DataModel\HabitSubscriptionDataModel;
use App\Domain\Gateway\Persister\HabitSubscriptionPersisterGateway;

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
