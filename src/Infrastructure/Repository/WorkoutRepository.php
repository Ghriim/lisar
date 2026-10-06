<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\DTO\DataModel\MovementDataModel;
use App\Domain\DTO\DataModel\SetTypeDataModel;
use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\DTO\DataModel\WorkoutDataModel;
use App\Domain\DTO\DataModel\WorkoutExerciseDataModel;
use App\Domain\DTO\DataModel\WorkoutSetDataModel;
use App\Domain\Gateway\Provider\WorkoutProviderGateway;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WorkoutDataModel>
 */
final class WorkoutRepository extends ServiceEntityRepository implements WorkoutProviderGateway
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkoutDataModel::class);
    }

    public function findOneByIdForOwner(int $id, UserDataModel $owner): ?WorkoutDataModel
    {
        return $this->whole()
            ->andWhere('workout.id = :id')
            ->andWhere('workout.owner = :owner')
            ->setParameter('id', $id)
            ->setParameter('owner', $owner)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneInProgressForOwner(UserDataModel $owner): ?WorkoutDataModel
    {
        // One at most by rule; should two ever exist, the latest started is the one carried on.
        $id = $this->createQueryBuilder('workout')
            ->select('workout.id')
            ->andWhere('workout.owner = :owner')
            ->andWhere('workout.finishedAt IS NULL')
            ->setParameter('owner', $owner)
            ->orderBy('workout.startedAt', 'DESC')
            ->addOrderBy('workout.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return null === $id ? null : $this->findOneByIdForOwner((int) $id['id'], $owner);
    }

    /** @return list<WorkoutDataModel> */
    public function findFinishedPageForOwner(UserDataModel $owner, int $offset, int $limit): array
    {
        // Paginated on ids first: a LIMIT over the fetch-joined rows would cut a workout in half.
        $ids = $this->finished($owner)
            ->select('workout.id')
            ->orderBy('workout.startedAt', 'DESC')
            ->addOrderBy('workout.id', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getSingleColumnResult();

        if ([] === $ids) {
            return [];
        }

        return $this->whole()
            ->andWhere('workout.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->orderBy('workout.startedAt', 'DESC')
            ->addOrderBy('workout.id', 'DESC')
            ->addOrderBy('block.position', 'ASC')
            ->addOrderBy('block.id', 'ASC')
            ->addOrderBy('exercise.position', 'ASC')
            ->addOrderBy('exercise.id', 'ASC')
            ->addOrderBy('workoutSet.position', 'ASC')
            ->addOrderBy('workoutSet.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countFinishedForOwner(UserDataModel $owner): int
    {
        return (int) $this->finished($owner)
            ->select('COUNT(workout.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countFinishedForOwnerBetween(UserDataModel $owner, DateTimeImmutable $from, DateTimeImmutable $to): int
    {
        return (int) $this->finished($owner)
            ->select('COUNT(workout.id)')
            ->andWhere('workout.finishedAt >= :from')
            ->andWhere('workout.finishedAt < :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findLastFinishedWithMovementBefore(UserDataModel $owner, MovementDataModel $movement, DateTimeImmutable $before): ?WorkoutDataModel
    {
        $id = $this->finished($owner)
            ->select('workout.id')
            ->innerJoin('workout.blocks', 'block')
            ->innerJoin('block.exercises', 'exercise')
            ->andWhere('exercise.movement = :movement')
            ->andWhere('workout.startedAt < :before')
            ->setParameter('movement', $movement)
            ->setParameter('before', $before)
            ->orderBy('workout.startedAt', 'DESC')
            ->addOrderBy('workout.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return null === $id ? null : $this->findOneByIdForOwner((int) $id['id'], $owner);
    }

    public function countExercisesForMovement(MovementDataModel $movement): int
    {
        return (int) $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(exercise.id)')
            ->from(WorkoutExerciseDataModel::class, 'exercise')
            ->andWhere('exercise.movement = :movement')
            ->setParameter('movement', $movement)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countSetsForSetType(SetTypeDataModel $setType): int
    {
        return (int) $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(workoutSet.id)')
            ->from(WorkoutSetDataModel::class, 'workoutSet')
            ->andWhere('workoutSet.setType = :setType')
            ->setParameter('setType', $setType)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** A workout with everything its output reads, joined and selected. */
    private function whole(): QueryBuilder
    {
        return $this->createQueryBuilder('workout')
            ->leftJoin('workout.blocks', 'block')
            ->leftJoin('block.exercises', 'exercise')
            ->leftJoin('exercise.movement', 'movement')
            ->leftJoin('exercise.sets', 'workoutSet')
            ->leftJoin('workoutSet.setType', 'setType')
            ->addSelect('block', 'exercise', 'movement', 'workoutSet', 'setType');
    }

    private function finished(UserDataModel $owner): QueryBuilder
    {
        return $this->createQueryBuilder('workout')
            ->andWhere('workout.owner = :owner')
            ->andWhere('workout.finishedAt IS NOT NULL')
            ->setParameter('owner', $owner);
    }
}
