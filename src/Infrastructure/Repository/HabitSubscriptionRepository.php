<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\DTO\DataModel\HabitDataModel;
use App\Domain\DTO\DataModel\HabitSubscriptionDataModel;
use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\Gateway\Provider\HabitSubscriptionProviderGateway;
use App\Domain\Registry\Habit\HabitSourceRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<HabitSubscriptionDataModel>
 */
final class HabitSubscriptionRepository extends ServiceEntityRepository implements HabitSubscriptionProviderGateway
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HabitSubscriptionDataModel::class);
    }

    public function findOneForOwnerAndHabit(UserDataModel $owner, HabitDataModel $habit): ?HabitSubscriptionDataModel
    {
        return $this->createQueryBuilder('subscription')
            ->andWhere('subscription.owner = :owner')
            ->andWhere('subscription.habit = :habit')
            ->setParameter('owner', $owner)
            ->setParameter('habit', $habit)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return list<HabitSubscriptionDataModel> */
    public function findActiveForOwner(UserDataModel $owner): array
    {
        return $this->activeForOwner($owner)
            ->orderBy('habit.name', 'ASC')
            ->addOrderBy('habit.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return list<HabitSubscriptionDataModel> */
    public function findActiveForOwnerAndTracker(UserDataModel $owner, string $trackerKind): array
    {
        return $this->activeForOwner($owner)
            ->andWhere('habit.sourceKind = :tracker')
            ->andWhere('habit.trackerKind = :trackerKind')
            ->setParameter('tracker', HabitSourceRegistry::TRACKER)
            ->setParameter('trackerKind', $trackerKind)
            ->getQuery()
            ->getResult();
    }

    /**
     * The person's active subscriptions to still-active habits, habit joined and selected — the
     * base every read here narrows.
     */
    private function activeForOwner(UserDataModel $owner): \Doctrine\ORM\QueryBuilder
    {
        return $this->createQueryBuilder('subscription')
            ->innerJoin('subscription.habit', 'habit')
            ->addSelect('habit')
            ->andWhere('subscription.owner = :owner')
            ->andWhere('subscription.isActive = :active')
            ->andWhere('habit.isActive = :active')
            ->setParameter('owner', $owner)
            ->setParameter('active', true);
    }
}
