<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\DTO\DataModel\PriorityDataModel;
use App\Domain\Gateway\Provider\PriorityProviderGateway;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PriorityDataModel>
 */
final class PriorityRepository extends ServiceEntityRepository implements PriorityProviderGateway
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PriorityDataModel::class);
    }

    public function findOneById(int $id): ?PriorityDataModel
    {
        return $this->createQueryBuilder('priority')
            ->andWhere('priority.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneDefault(): ?PriorityDataModel
    {
        return $this->createQueryBuilder('priority')
            ->andWhere('priority.isDefault = true')
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult();
    }

    /** @return list<PriorityDataModel> */
    public function findAllOrderedByWeight(): array
    {
        return $this->createQueryBuilder('priority')
            ->orderBy('priority.weight', 'ASC')
            ->addOrderBy('priority.label', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
