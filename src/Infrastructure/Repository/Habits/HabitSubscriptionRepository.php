<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository\Habits;

use App\Domain\DTO\Aggregate\Habits\HabitSubscriptionDay;
use App\Domain\DTO\DataModel\Habits\HabitDataModel;
use App\Domain\DTO\DataModel\Habits\HabitEntryDataModel;
use App\Domain\DTO\DataModel\Habits\HabitSubscriptionDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Gateway\Provider\Habits\HabitSubscriptionProviderGateway;
use App\Domain\Registry\Habits\HabitSourceRegistry;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Query\Expr\Join;
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

    /** @return list<HabitSubscriptionDay> */
    public function findActiveForOwnerAndTrackerWithEntryOn(UserDataModel $owner, string $trackerKind, DateTimeImmutable $day): array
    {
        // No collection of entries on a subscription: the entry is joined as a second root, and
        // the rows come back flat, each subscription followed by its entry, or by null.
        $rows = $this->activeForOwner($owner)
            ->leftJoin(HabitEntryDataModel::class, 'entry', Join::WITH, 'entry.subscription = subscription AND entry.day = :day')
            ->addSelect('entry')
            ->andWhere('habit.sourceKind = :tracker')
            ->andWhere('habit.trackerKind = :trackerKind')
            ->setParameter('tracker', HabitSourceRegistry::TRACKER)
            ->setParameter('trackerKind', $trackerKind)
            ->setParameter('day', $day, Types::DATE_IMMUTABLE)
            ->getQuery()
            ->getResult();

        $days = [];
        foreach (array_chunk($rows, 2) as [$subscription, $entry]) {
            $days[] = new HabitSubscriptionDay($subscription, $entry);
        }

        return $days;
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
