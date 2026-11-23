<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository\Training;

use App\Domain\DTO\DataModel\Training\RoutineExerciseDataModel;
use App\Domain\Gateway\Provider\Training\RoutineExerciseProviderGateway;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RoutineExerciseDataModel>
 */
final class RoutineExerciseRepository extends ServiceEntityRepository implements RoutineExerciseProviderGateway
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RoutineExerciseDataModel::class);
    }

    public function findOneById(int $id): ?RoutineExerciseDataModel
    {
        return $this->createQueryBuilder('exercise')
            ->andWhere('exercise.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
