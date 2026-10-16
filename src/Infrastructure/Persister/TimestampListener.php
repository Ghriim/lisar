<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister;

use App\Domain\DTO\DataModel\DataModelInterface;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Psr\Clock\ClockInterface;

/**
 * Stamps `createdAt` and `updatedAt` on every data model Doctrine writes — a row persisted on its
 * own or through a cascade, a row a flush updates — so no persister, factory or fixture does.
 * Time comes from the clock, never from `new \DateTimeImmutable()`: that is what makes timestamps
 * assertable in tests.
 *
 * `updatedAt` moves with a row's own columns: a flush that changes nothing on it, or only the rows
 * of a link table, does not touch it. Setting it in `preUpdate` is enough: the unit of work
 * recomputes the row's change set once the listeners have run.
 */
#[AsDoctrineListener(event: Events::prePersist)]
#[AsDoctrineListener(event: Events::preUpdate)]
final readonly class TimestampListener
{
    public function __construct(private ClockInterface $clock)
    {
    }

    public function prePersist(PrePersistEventArgs $args): void
    {
        $dataModel = $args->getObject();
        if (false === $dataModel instanceof DataModelInterface) {
            return;
        }

        $now = $this->now();
        if (true === property_exists($dataModel, 'createdAt')) {
            $dataModel->createdAt = $now;
        }
        if (true === property_exists($dataModel, 'updatedAt')) {
            $dataModel->updatedAt = $now;
        }
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $dataModel = $args->getObject();
        if (false === $dataModel instanceof DataModelInterface || false === property_exists($dataModel, 'updatedAt')) {
            return;
        }

        $dataModel->updatedAt = $this->now();
    }

    private function now(): DateTimeImmutable
    {
        return DateTimeImmutable::createFromInterface($this->clock->now());
    }
}
