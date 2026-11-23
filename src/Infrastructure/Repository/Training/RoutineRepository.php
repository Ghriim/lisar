<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository\Training;

use App\Domain\DTO\DataModel\Training\MovementDataModel;
use App\Domain\DTO\DataModel\Training\RoutineDataModel;
use App\Domain\DTO\DataModel\Training\RoutineExerciseDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Gateway\Provider\Training\RoutineProviderGateway;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RoutineDataModel>
 */
final class RoutineRepository extends ServiceEntityRepository implements RoutineProviderGateway
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RoutineDataModel::class);
    }

    public function findOneById(int $id): ?RoutineDataModel
    {
        return $this->createQueryBuilder('routine')
            ->leftJoin('routine.blocks', 'block')
            ->leftJoin('block.exercises', 'exercise')
            ->leftJoin('exercise.movement', 'movement')
            // Whether a movement is still offered reads its family: a start leaves out what is not.
            ->leftJoin('movement.movementFamily', 'movementFamily')
            ->leftJoin('exercise.sets', 'routineSet')
            ->addSelect('block', 'exercise', 'movement', 'movementFamily', 'routineSet')
            ->andWhere('routine.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return list<RoutineDataModel> */
    public function findAllForOwner(UserDataModel $owner): array
    {
        // The column's collation compares accents and case aside: « Épaules » sits among the E.
        return $this->createQueryBuilder('routine')
            ->leftJoin('routine.blocks', 'block')
            ->leftJoin('block.exercises', 'exercise')
            ->addSelect('block', 'exercise')
            ->andWhere('routine.owner = :owner')
            ->setParameter('owner', $owner)
            ->orderBy('routine.name', 'ASC')
            ->addOrderBy('routine.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countExercisesForMovement(MovementDataModel $movement): int
    {
        return (int) $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(exercise.id)')
            ->from(RoutineExerciseDataModel::class, 'exercise')
            ->andWhere('exercise.movement = :movement')
            ->setParameter('movement', $movement)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
