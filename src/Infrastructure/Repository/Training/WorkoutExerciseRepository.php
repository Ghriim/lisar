<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository\Training;

use App\Domain\DTO\DataModel\Training\WorkoutExerciseDataModel;
use App\Domain\Gateway\Provider\Training\WorkoutExerciseProviderGateway;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WorkoutExerciseDataModel>
 */
final class WorkoutExerciseRepository extends ServiceEntityRepository implements WorkoutExerciseProviderGateway
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkoutExerciseDataModel::class);
    }

    public function findOneById(int $id): ?WorkoutExerciseDataModel
    {
        return $this->createQueryBuilder('exercise')
            ->andWhere('exercise.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
