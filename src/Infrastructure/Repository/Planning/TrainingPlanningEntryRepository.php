<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository\Planning;

use App\Domain\DTO\DataModel\Planning\TrainingPlanningEntryDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Gateway\Provider\Planning\TrainingPlanningEntryProviderGateway;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TrainingPlanningEntryDataModel>
 */
final class TrainingPlanningEntryRepository extends ServiceEntityRepository implements TrainingPlanningEntryProviderGateway
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TrainingPlanningEntryDataModel::class);
    }

    public function findOneById(int $id): ?TrainingPlanningEntryDataModel
    {
        return $this->createQueryBuilder('entry')
            ->innerJoin('entry.routine', 'routine')
            ->addSelect('routine')
            ->andWhere('entry.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return list<TrainingPlanningEntryDataModel> */
    public function findAllForOwner(UserDataModel $owner): array
    {
        return $this->createQueryBuilder('entry')
            ->innerJoin('entry.routine', 'routine')
            ->addSelect('routine')
            ->andWhere('entry.owner = :owner')
            ->setParameter('owner', $owner)
            // Within the day first: a null start time sorts before any other in MySQL.
            ->orderBy('entry.startTimeInMinutes', 'ASC')
            ->addOrderBy('entry.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
