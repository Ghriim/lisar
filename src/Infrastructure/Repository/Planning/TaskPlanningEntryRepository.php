<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository\Planning;

use App\Domain\DTO\DataModel\Planning\TaskPlanningEntryDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Gateway\Provider\Planning\TaskPlanningEntryProviderGateway;
use App\Domain\Registry\Planning\PlanningRuleRegistry;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TaskPlanningEntryDataModel>
 */
final class TaskPlanningEntryRepository extends ServiceEntityRepository implements TaskPlanningEntryProviderGateway
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TaskPlanningEntryDataModel::class);
    }

    public function findOneById(int $id): ?TaskPlanningEntryDataModel
    {
        return $this->createQueryBuilder('entry')
            ->leftJoin('entry.category', 'category')
            ->addSelect('category')
            ->andWhere('entry.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return list<TaskPlanningEntryDataModel> */
    public function findAllForOwner(UserDataModel $owner): array
    {
        return $this->createQueryBuilder('entry')
            ->leftJoin('entry.category', 'category')
            ->addSelect('category')
            ->andWhere('entry.owner = :owner')
            ->setParameter('owner', $owner)
            // Within the day first: a null start time sorts before any other in MySQL.
            ->orderBy('entry.startTimeInMinutes', 'ASC')
            ->addOrderBy('entry.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return list<TaskPlanningEntryDataModel> */
    public function findRecurringSpanning(DateTimeImmutable $day): array
    {
        return $this->createQueryBuilder('entry')
            ->innerJoin('entry.owner', 'owner')
            ->addSelect('owner')
            ->leftJoin('entry.category', 'category')
            ->addSelect('category')
            ->andWhere('entry.rule <> :once')
            ->andWhere('entry.startDate <= :day')
            ->andWhere('entry.endDate IS NULL OR entry.endDate >= :day')
            ->setParameter('once', PlanningRuleRegistry::ONCE)
            ->setParameter('day', $day, Types::DATE_IMMUTABLE)
            ->orderBy('entry.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
