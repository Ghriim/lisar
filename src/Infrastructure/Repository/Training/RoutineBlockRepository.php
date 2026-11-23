<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository\Training;

use App\Domain\DTO\DataModel\Training\RoutineBlockDataModel;
use App\Domain\Gateway\Provider\Training\RoutineBlockProviderGateway;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RoutineBlockDataModel>
 */
final class RoutineBlockRepository extends ServiceEntityRepository implements RoutineBlockProviderGateway
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RoutineBlockDataModel::class);
    }

    public function findOneById(int $id): ?RoutineBlockDataModel
    {
        return $this->createQueryBuilder('block')
            ->andWhere('block.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
