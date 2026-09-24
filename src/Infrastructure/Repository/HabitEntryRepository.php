<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\DTO\DataModel\HabitEntryDataModel;
use App\Domain\DTO\DataModel\HabitSubscriptionDataModel;
use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\Gateway\Provider\HabitEntryProviderGateway;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<HabitEntryDataModel>
 */
final class HabitEntryRepository extends ServiceEntityRepository implements HabitEntryProviderGateway
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HabitEntryDataModel::class);
    }

    public function findOneForSubscriptionAndDay(HabitSubscriptionDataModel $subscription, DateTimeImmutable $day): ?HabitEntryDataModel
    {
        return $this->createQueryBuilder('entry')
            ->andWhere('entry.subscription = :subscription')
            ->andWhere('entry.day = :day')
            ->setParameter('subscription', $subscription)
            ->setParameter('day', $day)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return list<HabitEntryDataModel> */
    public function findForOwnerSince(UserDataModel $owner, DateTimeImmutable $since): array
    {
        return $this->createQueryBuilder('entry')
            ->innerJoin('entry.subscription', 'subscription')
            ->addSelect('subscription')
            ->andWhere('subscription.owner = :owner')
            ->andWhere('entry.day >= :since')
            ->setParameter('owner', $owner)
            ->setParameter('since', $since)
            ->getQuery()
            ->getResult();
    }

    /** @return list<HabitEntryDataModel> */
    public function findForSubscriptionSince(HabitSubscriptionDataModel $subscription, DateTimeImmutable $since): array
    {
        return $this->createQueryBuilder('entry')
            ->andWhere('entry.subscription = :subscription')
            ->andWhere('entry.day >= :since')
            ->setParameter('subscription', $subscription)
            ->setParameter('since', $since)
            ->getQuery()
            ->getResult();
    }
}
