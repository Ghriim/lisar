<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository\Training;

use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutSummaryDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Gateway\Provider\Training\WorkoutSummaryProviderGateway;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WorkoutSummaryDataModel>
 */
final class WorkoutSummaryRepository extends ServiceEntityRepository implements WorkoutSummaryProviderGateway
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkoutSummaryDataModel::class);
    }

    public function findOneForWorkout(WorkoutDataModel $workout): ?WorkoutSummaryDataModel
    {
        return $this->createQueryBuilder('summary')
            ->andWhere('summary.workout = :workout')
            ->setParameter('workout', $workout)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return array<int, WorkoutSummaryDataModel> */
    public function findAllForWorkouts(array $workouts): array
    {
        if ([] === $workouts) {
            return [];
        }

        /** @var list<WorkoutSummaryDataModel> $summaries */
        $summaries = $this->createQueryBuilder('summary')
            ->andWhere('summary.workout IN (:workouts)')
            ->setParameter('workouts', $workouts)
            ->getQuery()
            ->getResult();

        $byWorkout = [];
        foreach ($summaries as $summary) {
            $byWorkout[(int) $summary->workout->id] = $summary;
        }

        return $byWorkout;
    }

    /** @return list<WorkoutSummaryDataModel> */
    public function findAllForOwner(UserDataModel $owner): array
    {
        return $this->createQueryBuilder('summary')
            ->innerJoin('summary.workout', 'workout')
            ->addSelect('workout')
            ->andWhere('workout.owner = :owner')
            ->setParameter('owner', $owner)
            ->orderBy('workout.startedAt', 'ASC')
            ->addOrderBy('workout.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
