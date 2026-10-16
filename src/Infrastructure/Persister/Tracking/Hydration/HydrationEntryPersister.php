<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister\Tracking\Hydration;

use App\Domain\DTO\DataModel\Tracking\HydrationEntryDataModel;
use App\Domain\DTO\Event\Tracking\HydrationEntryCreatedEvent;
use App\Domain\DTO\Event\Tracking\HydrationEntryDeletedEvent;
use App\Domain\DTO\Event\Tracking\HydrationEntryUpdatedEvent;
use App\Domain\Event\EventDispatcherInterface;
use App\Domain\Gateway\Persister\Tracking\Hydration\HydrationEntryPersisterGateway;
use App\Infrastructure\Persister\AbstractBaseMysqlPersister;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @extends AbstractBaseMysqlPersister<HydrationEntryDataModel>
 */
final class HydrationEntryPersister extends AbstractBaseMysqlPersister implements HydrationEntryPersisterGateway
{
    public function __construct(
        EntityManagerInterface $entityManager,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
        parent::__construct($entityManager);
    }

    public function create(HydrationEntryDataModel $entry): HydrationEntryDataModel
    {
        $this->inTransaction(function () use ($entry): void {
            $this->persistCreate($entry);
            // Both sides kept in step before anyone reads the day's total off its entries.
            if (false === $entry->hydrationDay->entries->contains($entry)) {
                $entry->hydrationDay->entries->add($entry);
            }
            $this->eventDispatcher->dispatch(new HydrationEntryCreatedEvent($entry));
        });

        return $entry;
    }

    public function update(HydrationEntryDataModel $entry): HydrationEntryDataModel
    {
        $this->inTransaction(function () use ($entry): void {
            $this->persistUpdate($entry);
            $this->eventDispatcher->dispatch(new HydrationEntryUpdatedEvent($entry));
        });

        return $entry;
    }

    public function delete(HydrationEntryDataModel $entry): void
    {
        $day = $entry->hydrationDay;

        $this->inTransaction(function () use ($entry, $day): void {
            $this->persistDelete($entry);
            $day->entries->removeElement($entry);
            $this->eventDispatcher->dispatch(new HydrationEntryDeletedEvent($day));
        });
    }
}
