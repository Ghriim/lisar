<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister\Habits;

use App\Domain\DTO\DataModel\Habits\HabitSubscriptionDataModel;
use App\Domain\DTO\Event\Habits\HabitSubscriptionCreatedEvent;
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
        $this->inTransaction(function () use ($subscription): void {
            $this->persistCreate($subscription);
            $this->eventDispatcher->dispatch(new HabitSubscriptionCreatedEvent($subscription));
        });

        return $subscription;
    }

    public function update(HabitSubscriptionDataModel $subscription): HabitSubscriptionDataModel
    {
        // Read before the flush forgets it: only the write that clears it drops the habit, only the
        // one that sets it again takes it back up.
        $wasActive = true === $this->originalValueOf($subscription, 'isActive');
        $deactivates = true === $wasActive && false === $subscription->isActive;
        $activates = false === $wasActive && true === $subscription->isActive;

        $this->inTransaction(function () use ($subscription, $deactivates, $activates): void {
            $this->persistUpdate($subscription);
            $this->eventDispatcher->dispatch(new HabitSubscriptionUpdatedEvent($subscription, $deactivates, $activates));
        });

        return $subscription;
    }
}
