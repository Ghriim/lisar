<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository\Training;

use App\Domain\DTO\DataModel\Training\RoutineSetDataModel;
use App\Domain\Gateway\Provider\Training\RoutineSetProviderGateway;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RoutineSetDataModel>
 */
final class RoutineSetRepository extends ServiceEntityRepository implements RoutineSetProviderGateway
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RoutineSetDataModel::class);
    }

    public function findOneById(int $id): ?RoutineSetDataModel
    {
        return $this->createQueryBuilder('routineSet')
            ->andWhere('routineSet.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
