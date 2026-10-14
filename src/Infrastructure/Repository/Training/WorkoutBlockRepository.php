<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository\Training;

use App\Domain\DTO\DataModel\Training\WorkoutBlockDataModel;
use App\Domain\Gateway\Provider\Training\WorkoutBlockProviderGateway;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WorkoutBlockDataModel>
 */
final class WorkoutBlockRepository extends ServiceEntityRepository implements WorkoutBlockProviderGateway
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkoutBlockDataModel::class);
    }

    public function findOneById(int $id): ?WorkoutBlockDataModel
    {
        return $this->createQueryBuilder('block')
            ->andWhere('block.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
