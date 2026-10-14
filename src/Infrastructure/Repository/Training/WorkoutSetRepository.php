<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository\Training;

use App\Domain\DTO\DataModel\Training\WorkoutSetDataModel;
use App\Domain\Gateway\Provider\Training\WorkoutSetProviderGateway;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WorkoutSetDataModel>
 */
final class WorkoutSetRepository extends ServiceEntityRepository implements WorkoutSetProviderGateway
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkoutSetDataModel::class);
    }

    public function findOneById(int $id): ?WorkoutSetDataModel
    {
        return $this->createQueryBuilder('workoutSet')
            ->andWhere('workoutSet.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
