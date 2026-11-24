<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister\Planning;

use App\Domain\DTO\DataModel\Habits\HabitSubscriptionDataModel;
use App\Domain\DTO\DataModel\Planning\HabitPlanningEntryDataModel;
use App\Domain\Gateway\Persister\Planning\HabitPlanningEntryPersisterGateway;
use App\Infrastructure\Persister\AbstractBaseMysqlPersister;

/**
 * Nothing follows from a habit entry's writes: no event is raised. Whether a date was kept is read
 * from the habit's days, never written here.
 *
 * @extends AbstractBaseMysqlPersister<HabitPlanningEntryDataModel>
 */
final class HabitPlanningEntryPersister extends AbstractBaseMysqlPersister implements HabitPlanningEntryPersisterGateway
{
    public function create(HabitPlanningEntryDataModel $entry): HabitPlanningEntryDataModel
    {
        return $this->persistCreate($entry);
    }

    public function update(HabitPlanningEntryDataModel $entry): HabitPlanningEntryDataModel
    {
        return $this->persistUpdate($entry);
    }

    public function delete(HabitPlanningEntryDataModel $entry): void
    {
        $this->persistDelete($entry);
    }

    public function deleteAllForSubscription(HabitSubscriptionDataModel $subscription): void
    {
        $this->entityManager->createQueryBuilder()
            ->delete(HabitPlanningEntryDataModel::class, 'entry')
            ->andWhere('entry.subscription = :subscription')
            ->setParameter('subscription', $subscription)
            ->getQuery()
            ->execute();

        // What the request had loaded of them is gone from the table: left in the identity map, a
        // later flush would write them back.
        foreach ($this->entityManager->getUnitOfWork()->getIdentityMap()[HabitPlanningEntryDataModel::class] ?? [] as $loaded) {
            if ($loaded instanceof HabitPlanningEntryDataModel && $loaded->subscription === $subscription) {
                $this->entityManager->detach($loaded);
            }
        }
    }
}
