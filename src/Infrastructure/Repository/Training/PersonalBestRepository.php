<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository\Training;

use App\Domain\DTO\DataModel\Training\MovementDataModel;
use App\Domain\DTO\DataModel\Training\PersonalBestDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Gateway\Provider\Training\PersonalBestProviderGateway;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PersonalBestDataModel>
 */
final class PersonalBestRepository extends ServiceEntityRepository implements PersonalBestProviderGateway
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PersonalBestDataModel::class);
    }

    /** @return list<PersonalBestDataModel> */
    public function findAllForOwnerAndMovement(UserDataModel $owner, ?MovementDataModel $movement): array
    {
        $queryBuilder = $this->ordered()->andWhere('personalBest.owner = :owner')->setParameter('owner', $owner);

        if (null === $movement) {
            $queryBuilder->andWhere('personalBest.movement IS NULL');
        } else {
            $queryBuilder->andWhere('personalBest.movement = :movement')->setParameter('movement', $movement);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /** @return list<PersonalBestDataModel> */
    public function findAllForOwner(UserDataModel $owner): array
    {
        return $this->ordered()
            ->andWhere('personalBest.owner = :owner')
            ->setParameter('owner', $owner)
            ->getQuery()
            ->getResult();
    }

    /** @return list<PersonalBestDataModel> */
    public function findAllForWorkout(WorkoutDataModel $workout): array
    {
        return $this->ordered()
            ->andWhere('personalBest.workout = :workout')
            ->setParameter('workout', $workout)
            ->getQuery()
            ->getResult();
    }

    /** Oldest first, with what an output reads of a row. */
    private function ordered(): QueryBuilder
    {
        return $this->createQueryBuilder('personalBest')
            ->leftJoin('personalBest.movement', 'movement')
            ->leftJoin('movement.movementFamily', 'movementFamily')
            ->innerJoin('personalBest.workout', 'workout')
            ->leftJoin('personalBest.set', 'workoutSet')
            ->addSelect('movement', 'movementFamily', 'workout', 'workoutSet')
            ->orderBy('personalBest.achievedAt', 'ASC')
            ->addOrderBy('personalBest.id', 'ASC');
    }
}
