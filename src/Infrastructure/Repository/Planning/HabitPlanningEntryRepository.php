<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository\Planning;

use App\Domain\DTO\DataModel\Habits\HabitSubscriptionDataModel;
use App\Domain\DTO\DataModel\Planning\HabitPlanningEntryDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Gateway\Provider\Planning\HabitPlanningEntryProviderGateway;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<HabitPlanningEntryDataModel>
 */
final class HabitPlanningEntryRepository extends ServiceEntityRepository implements HabitPlanningEntryProviderGateway
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HabitPlanningEntryDataModel::class);
    }

    public function findOneById(int $id): ?HabitPlanningEntryDataModel
    {
        return $this->createQueryBuilder('entry')
            ->innerJoin('entry.subscription', 'subscription')
            ->addSelect('subscription')
            ->innerJoin('subscription.habit', 'habit')
            ->addSelect('habit')
            // The habit's icon is its category's.
            ->leftJoin('habit.category', 'category')
            ->addSelect('category')
            ->andWhere('entry.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return list<HabitPlanningEntryDataModel> */
    public function findAllForOwner(UserDataModel $owner): array
    {
        return $this->createQueryBuilder('entry')
            ->innerJoin('entry.subscription', 'subscription')
            ->addSelect('subscription')
            ->innerJoin('subscription.habit', 'habit')
            ->addSelect('habit')
            // The habit's icon is its category's.
            ->leftJoin('habit.category', 'category')
            ->addSelect('category')
            ->andWhere('entry.owner = :owner')
            ->andWhere('subscription.isActive = true')
            ->andWhere('habit.isActive = true')
            ->setParameter('owner', $owner)
            // Within the day first: a null start time sorts before any other in MySQL.
            ->orderBy('entry.startTimeInMinutes', 'ASC')
            ->addOrderBy('entry.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return list<HabitPlanningEntryDataModel> */
    public function findForSubscription(HabitSubscriptionDataModel $subscription): array
    {
        return $this->createQueryBuilder('entry')
            ->andWhere('entry.subscription = :subscription')
            ->setParameter('subscription', $subscription)
            ->getQuery()
            ->getResult();
    }
}
