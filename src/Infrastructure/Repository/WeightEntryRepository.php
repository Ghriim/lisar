<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\DTO\DataModel\WeightEntryDataModel;
use App\Domain\Gateway\Provider\WeightEntryProviderGateway;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WeightEntryDataModel>
 */
final class WeightEntryRepository extends ServiceEntityRepository implements WeightEntryProviderGateway
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WeightEntryDataModel::class);
    }

    public function findLatestForOwner(UserDataModel $owner): ?WeightEntryDataModel
    {
        // Ordered on the day, not on recordedAt: the day is what the measurement belongs to, and
        // a correction made this morning to yesterday's row must not make it the latest.
        return $this->createQueryBuilder('weightEntry')
            ->andWhere('weightEntry.owner = :owner')
            ->setParameter('owner', $owner)
            ->orderBy('weightEntry.day', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneForOwnerAndDay(UserDataModel $owner, DateTimeImmutable $day): ?WeightEntryDataModel
    {
        return $this->createQueryBuilder('weightEntry')
            ->andWhere('weightEntry.owner = :owner')
            ->andWhere('weightEntry.day = :day')
            ->setParameter('owner', $owner)
            ->setParameter('day', $day)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
