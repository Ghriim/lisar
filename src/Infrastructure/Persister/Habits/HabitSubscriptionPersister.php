<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister\Habits;

use App\Domain\DTO\DataModel\Habits\HabitSubscriptionDataModel;
use App\Domain\DTO\Event\Habits\HabitSubscriptionUpdatedEvent;
use App\Domain\Event\EventDispatcherInterface;
use App\Domain\Gateway\Persister\Habits\HabitSubscriptionPersisterGateway;
use App\Infrastructure\Persister\AbstractBaseMysqlPersister;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @extends AbstractBaseMysqlPersister<HabitSubscriptionDataModel>
 */
final class HabitSubscriptionPersister extends AbstractBaseMysqlPersister implements HabitSubscriptionPersisterGateway
{
    public function __construct(
        EntityManagerInterface $entityManager,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
        parent::__construct($entityManager);
    }

    public function create(HabitSubscriptionDataModel $subscription): HabitSubscriptionDataModel
    {
        return $this->persistCreate($subscription);
    }

    public function update(HabitSubscriptionDataModel $subscription): HabitSubscriptionDataModel
    {
        // Read before the flush forgets it: only the write that clears it drops the habit.
        $deactivates = true === $this->originalValueOf($subscription, 'isActive') && false === $subscription->isActive;

        $this->inTransaction(function () use ($subscription, $deactivates): void {
            $this->persistUpdate($subscription);
            $this->eventDispatcher->dispatch(new HabitSubscriptionUpdatedEvent($subscription, $deactivates));
        });

        return $subscription;
    }
}
