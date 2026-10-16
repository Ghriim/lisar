<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister;

use App\Domain\DTO\DataModel\DataModelInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ToOneOwningSideMapping;
use Throwable;

use function is_object;
use function spl_object_id;

/**
 * Owns the flush. A persister whose writes others react to raises an event after its flush,
 * inside `inTransaction`, so the handlers and the write stand or fall together. The timestamps are
 * not its business: `TimestampListener` stamps every row Doctrine writes.
 *
 * @template T of DataModelInterface
 */
abstract class AbstractBaseMysqlPersister
{
    public function __construct(
        protected EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param T $dataModel
     *
     * @return T
     */
    protected function persistCreate(DataModelInterface $dataModel, bool $flush = true): DataModelInterface
    {
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
    protected function persistUpdate(DataModelInterface $dataModel, bool $flush = true): DataModelInterface
    {
        $this->entityManager->persist($dataModel);

        if (true === $flush) {
            $this->entityManager->flush();
        }

        return $dataModel;
    }

    /**
     * Deletes the row alone: its children go with it through the database (ON DELETE CASCADE),
     * never one by one from here (backend-architecture.md §6.1).
     *
     * @param T $dataModel
     */
    protected function persistDelete(DataModelInterface $dataModel, bool $flush = true): void
    {
        $this->entityManager->remove($dataModel);
        $this->detachWhatTheDatabaseCascades($dataModel);

        if (true === $flush) {
            $this->entityManager->flush();
        }
    }

    /**
     * What the unit of work had loaded of the cascaded children is only detached, without a
     * query: left in the identity map, they would point at a parent that is gone, and the next
     * flush would take that parent for a new row.
     *
     * It follows the mapping, so it mirrors the database: every loaded row whose CASCADE foreign
     * key points at a removed one goes, and the rows that point at it in turn.
     */
    private function detachWhatTheDatabaseCascades(object $removed): void
    {
        $unitOfWork = $this->entityManager->getUnitOfWork();
        $removedIds = [spl_object_id($removed) => true];

        do {
            $detached = [];
            foreach ($unitOfWork->getIdentityMap() as $loaded) {
                foreach ($loaded as $entity) {
                    if (false === isset($removedIds[spl_object_id($entity)]) && true === $this->isCascadedFrom($entity, $removedIds)) {
                        $detached[] = $entity;
                    }
                }
            }

            foreach ($detached as $entity) {
                $unitOfWork->detach($entity);
                $removedIds[spl_object_id($entity)] = true;
            }
        } while ([] !== $detached);
    }

    /**
     * @param array<int, true> $removedIds
     */
    private function isCascadedFrom(object $entity, array $removedIds): bool
    {
        $metadata = $this->entityManager->getClassMetadata($entity::class);

        foreach ($metadata->getAssociationMappings() as $association) {
            if (false === $association instanceof ToOneOwningSideMapping || 'CASCADE' !== ($association->joinColumns[0]->onDelete ?? null)) {
                continue;
            }

            $parent = $metadata->getFieldValue($entity, $association->fieldName);
            if (true === is_object($parent) && true === isset($removedIds[spl_object_id($parent)])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Runs a write and the handlers of what it raised as one transaction: a handler that throws
     * takes the write back with it, so a write never stands without what follows from it.
     */
    protected function inTransaction(callable $operation): void
    {
        $connection = $this->entityManager->getConnection();
        $connection->beginTransaction();

        try {
            $operation();
            $connection->commit();
        } catch (Throwable $exception) {
            $connection->rollBack();

            throw $exception;
        }
    }
}
