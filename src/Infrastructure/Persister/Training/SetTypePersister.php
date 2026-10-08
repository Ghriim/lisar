<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister\Training;

use App\Domain\DTO\DataModel\Training\SetTypeDataModel;
use App\Domain\DTO\Event\Training\SetTypeUpdatedEvent;
use App\Domain\Event\EventDispatcherInterface;
use App\Domain\Gateway\Persister\Training\SetTypePersisterGateway;
use App\Infrastructure\Persister\AbstractBaseMysqlPersister;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;

/**
 * @extends AbstractBaseMysqlPersister<SetTypeDataModel>
 */
final class SetTypePersister extends AbstractBaseMysqlPersister implements SetTypePersisterGateway
{
    public function __construct(
        EntityManagerInterface $entityManager,
        ClockInterface $clock,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
        parent::__construct($entityManager, $clock);
    }

    public function create(SetTypeDataModel $setType): SetTypeDataModel
    {
        return $this->persistAndStampCreate($setType);
    }

    public function update(SetTypeDataModel $setType): SetTypeDataModel
    {
        $this->updateMany([$setType]);

        return $setType;
    }

    /** @param SetTypeDataModel[] $setTypes */
    public function updateMany(array $setTypes): void
    {
        $events = [];
        foreach ($setTypes as $setType) {
            $events[] = new SetTypeUpdatedEvent($setType, $this->countsForPersonalBestsChanges($setType));
        }

        $this->inTransaction(function () use ($setTypes, $events): void {
            foreach ($setTypes as $setType) {
                $this->persistAndStampUpdate($setType, flush: false);
            }
            $this->entityManager->flush();

            foreach ($events as $event) {
                $this->eventDispatcher->dispatch($event);
            }
        });
    }

    public function delete(SetTypeDataModel $setType): void
    {
        $this->persistDelete($setType);
    }

    /** Read before the flush, which makes the new value the original. */
    private function countsForPersonalBestsChanges(SetTypeDataModel $setType): bool
    {
        $original = $this->entityManager->getUnitOfWork()->getOriginalEntityData($setType);

        return true === array_key_exists('countsForPersonalBests', $original)
            && $original['countsForPersonalBests'] !== $setType->countsForPersonalBests;
    }
}
