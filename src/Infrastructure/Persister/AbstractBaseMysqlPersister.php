<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister;

use App\Domain\DTO\DataModel\DataModelInterface;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;

/**
 * Stamps createdAt/updatedAt and owns the flush. Time comes from the clock, never from
 * `new \DateTimeImmutable()`: that is what makes timestamps assertable in tests.
 *
 * @template T of DataModelInterface
 */
abstract class AbstractBaseMysqlPersister
{
    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected ClockInterface $clock,
    ) {
    }

    /**
     * @param T $dataModel
     *
     * @return T
     */
    protected function persistAndStampCreate(DataModelInterface $dataModel, bool $flush = true): DataModelInterface
    {
        $now = DateTimeImmutable::createFromInterface($this->clock->now());

        if (true === property_exists($dataModel, 'createdAt')) {
            $dataModel->createdAt = $now;
        }
        if (true === property_exists($dataModel, 'updatedAt')) {
            $dataModel->updatedAt = $now;
        }

        $this->entityManager->persist($dataModel);

        if (true === $flush) {
            $this->entityManager->flush();
        }

        return $dataModel;
    }

    /**
     * @param T $dataModel
     *
     * @return T
     */
    protected function persistAndStampUpdate(DataModelInterface $dataModel, bool $flush = true): DataModelInterface
    {
        if (true === property_exists($dataModel, 'updatedAt')) {
            $dataModel->updatedAt = DateTimeImmutable::createFromInterface($this->clock->now());
        }

        $this->entityManager->persist($dataModel);

        if (true === $flush) {
            $this->entityManager->flush();
        }

        return $dataModel;
    }

    /**
     * @param T $dataModel
     */
    protected function persistDelete(DataModelInterface $dataModel, bool $flush = true): void
    {
        $this->entityManager->remove($dataModel);

        if (true === $flush) {
            $this->entityManager->flush();
        }
    }
}
