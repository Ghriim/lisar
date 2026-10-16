<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository\Training;

use App\Domain\DTO\Aggregate\Training\WorkoutTally;
use App\Domain\DTO\DataModel\Training\MovementDataModel;
use App\Domain\DTO\DataModel\Training\SetTypeDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutExerciseDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutSetDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Gateway\Provider\Training\WorkoutProviderGateway;
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

    public function findOneById(int $id): ?WorkoutDataModel
    {
        return $this->whole()
            ->andWhere('workout.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneWithMusclesById(int $id): ?WorkoutDataModel
    {
        return $this->whole()
            ->leftJoin('movement.primaryMuscle', 'primaryMuscle')
            ->leftJoin('movement.secondaryMuscles', 'secondaryMuscle')
            ->addSelect('primaryMuscle', 'secondaryMuscle')
            ->andWhere('workout.id = :id')
            ->setParameter('id', $id)
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

        return null === $id ? null : $this->findOneById((int) $id['id']);
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

    /** @return array<int, WorkoutDataModel> */
    public function findLastFinishedWithMovementsBefore(UserDataModel $owner, array $movements, DateTimeImmutable $before): array
    {
        if ([] === $movements) {
            return [];
        }

        // A workout is the latest for a movement when no finished one of the owner holding that
        // movement came after it, still before `$before`: started later, or at the same instant
        // with a greater id — the order the page lists them in.
        $later = $this->getEntityManager()->createQueryBuilder()
            ->select('1')
            ->from(WorkoutExerciseDataModel::class, 'laterExercise')
            ->innerJoin('laterExercise.block', 'laterBlock')
            ->innerJoin('laterBlock.workout', 'laterWorkout')
            ->andWhere('laterExercise.movement = exercise.movement')
            ->andWhere('laterWorkout.owner = :owner')
            ->andWhere('laterWorkout.finishedAt IS NOT NULL')
            ->andWhere('laterWorkout.startedAt < :before')
            ->andWhere('laterWorkout.startedAt > workout.startedAt OR (laterWorkout.startedAt = workout.startedAt AND laterWorkout.id > workout.id)');

        $queryBuilder = $this->finished($owner);
        /** @var list<array{movementId: int|string, workoutId: int|string}> $rows */
        $rows = $queryBuilder
            ->select('IDENTITY(exercise.movement) AS movementId', 'workout.id AS workoutId')
            ->distinct()
            ->innerJoin('workout.blocks', 'block')
            ->innerJoin('block.exercises', 'exercise')
            ->andWhere('exercise.movement IN (:movements)')
            ->andWhere('workout.startedAt < :before')
            ->andWhere($queryBuilder->expr()->not($queryBuilder->expr()->exists($later->getDQL())))
            ->setParameter('movements', $movements)
            ->setParameter('before', $before)
            ->getQuery()
            ->getArrayResult();

        if ([] === $rows) {
            return [];
        }

        $workoutIdByMovementId = [];
        foreach ($rows as $row) {
            $workoutIdByMovementId[(int) $row['movementId']] = (int) $row['workoutId'];
        }

        /** @var list<WorkoutDataModel> $workouts */
        $workouts = $this->whole()
            ->andWhere('workout.id IN (:ids)')
            ->setParameter('ids', array_values(array_unique($workoutIdByMovementId)))
            ->getQuery()
            ->getResult();

        $workoutsById = [];
        foreach ($workouts as $workout) {
            $workoutsById[(int) $workout->id] = $workout;
        }

        $latest = [];
        foreach ($workoutIdByMovementId as $movementId => $workoutId) {
            $latest[$movementId] = $workoutsById[$workoutId];
        }

        return $latest;
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

    /** @return list<WorkoutSetDataModel> */
    public function findSetsCountingForPersonalBests(UserDataModel $owner, array $movements): array
    {
        if ([] === $movements) {
            return [];
        }

        return $this->getEntityManager()->createQueryBuilder()
            ->select('workoutSet', 'exercise', 'block', 'workout', 'setType')
            ->from(WorkoutSetDataModel::class, 'workoutSet')
            ->innerJoin('workoutSet.setType', 'setType')
            ->innerJoin('workoutSet.exercise', 'exercise')
            ->innerJoin('exercise.block', 'block')
            ->innerJoin('block.workout', 'workout')
            ->andWhere('workout.owner = :owner')
            ->andWhere('exercise.movement IN (:movements)')
            ->andWhere('workoutSet.isComplete = true')
            ->andWhere('setType.countsForPersonalBests = true')
            ->setParameter('owner', $owner)
            ->setParameter('movements', $movements)
            ->orderBy('workout.startedAt', 'ASC')
            ->addOrderBy('workout.id', 'ASC')
            ->addOrderBy('block.position', 'ASC')
            ->addOrderBy('block.id', 'ASC')
            ->addOrderBy('exercise.position', 'ASC')
            ->addOrderBy('exercise.id', 'ASC')
            ->addOrderBy('workoutSet.position', 'ASC')
            ->addOrderBy('workoutSet.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return list<WorkoutTally> */
    public function findTalliesForOwner(UserDataModel $owner): array
    {
        $counts = 'workoutSet.isComplete = true AND setType.countsForPersonalBests = true';
        $carriesLoad = $counts.' AND workoutSet.reps IS NOT NULL AND workoutSet.weightInKilograms IS NOT NULL';

        /** @var list<array{0: WorkoutDataModel, setCount: string|int, loadedSetCount: string|int, volume: string|float|null}> $rows */
        $rows = $this->createQueryBuilder('workout')
            ->addSelect("SUM(CASE WHEN {$counts} THEN 1 ELSE 0 END) AS setCount")
            ->addSelect("SUM(CASE WHEN {$carriesLoad} THEN 1 ELSE 0 END) AS loadedSetCount")
            ->addSelect("SUM(CASE WHEN {$carriesLoad} THEN workoutSet.reps * workoutSet.weightInKilograms ELSE 0 END) AS volume")
            ->leftJoin('workout.blocks', 'block')
            ->leftJoin('block.exercises', 'exercise')
            ->leftJoin('exercise.sets', 'workoutSet')
            ->leftJoin('workoutSet.setType', 'setType')
            ->andWhere('workout.owner = :owner')
            ->setParameter('owner', $owner)
            ->groupBy('workout.id')
            ->orderBy('workout.startedAt', 'ASC')
            ->addOrderBy('workout.id', 'ASC')
            ->getQuery()
            ->getResult();

        $tallies = [];
        foreach ($rows as $row) {
            $tallies[] = new WorkoutTally(
                $row[0],
                (int) $row['setCount'],
                0 === (int) $row['loadedSetCount'] ? null : (float) $row['volume'],
            );
        }

        return $tallies;
    }

    /** A workout with everything its output reads, joined and selected. */
    private function whole(): QueryBuilder
    {
        return $this->createQueryBuilder('workout')
            ->leftJoin('workout.blocks', 'block')
            ->leftJoin('block.exercises', 'exercise')
            ->leftJoin('exercise.movement', 'movement')
            // Whether a movement is still offered reads its family: a copy leaves out what is not.
            ->leftJoin('movement.movementFamily', 'movementFamily')
            ->leftJoin('exercise.sets', 'workoutSet')
            ->leftJoin('workoutSet.setType', 'setType')
            ->leftJoin('workoutSet.personalBests', 'setPersonalBest')
            ->addSelect('block', 'exercise', 'movement', 'movementFamily', 'workoutSet', 'setType', 'setPersonalBest');
    }

    private function finished(UserDataModel $owner): QueryBuilder
    {
        return $this->createQueryBuilder('workout')
            ->andWhere('workout.owner = :owner')
            ->andWhere('workout.finishedAt IS NOT NULL')
            ->setParameter('owner', $owner);
    }
}
