<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\DTO\DataModel\EquipmentDataModel;
use App\Domain\Gateway\Provider\EquipmentProviderGateway;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EquipmentDataModel>
 */
final class EquipmentRepository extends ServiceEntityRepository implements EquipmentProviderGateway
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EquipmentDataModel::class);
    }

    public function findOneById(int $id): ?EquipmentDataModel
    {
        return $this->createQueryBuilder('equipment')
            ->andWhere('equipment.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return list<EquipmentDataModel> */
    public function findByIds(array $ids): array
    {
        if ([] === $ids) {
            return [];
        }

        return $this->createQueryBuilder('equipment')
            ->andWhere('equipment.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();
    }

    public function findOneByName(string $name): ?EquipmentDataModel
    {
        return $this->createQueryBuilder('equipment')
            ->andWhere('equipment.name = :name')
            ->setParameter('name', $name)
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult();
    }

    /** @return list<EquipmentDataModel> */
    public function findAllForAdminList(?bool $isActive, ?bool $hasWeight, ?bool $hasDistance): array
    {
        $queryBuilder = $this->createQueryBuilder('equipment')
            ->orderBy('equipment.name', 'ASC')
            ->addOrderBy('equipment.id', 'ASC');

        if (null !== $isActive) {
            $queryBuilder->andWhere('equipment.isActive = :active')->setParameter('active', $isActive);
        }
        if (null !== $hasWeight) {
            $queryBuilder->andWhere('equipment.hasWeight = :hasWeight')->setParameter('hasWeight', $hasWeight);
        }
        if (null !== $hasDistance) {
            $queryBuilder->andWhere('equipment.hasDistance = :hasDistance')->setParameter('hasDistance', $hasDistance);
        }

        return $queryBuilder->getQuery()->getResult();
    }
}
