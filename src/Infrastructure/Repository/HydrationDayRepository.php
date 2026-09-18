<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\DTO\DataModel\HydrationDayDataModel;
use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\Gateway\Provider\HydrationDayProviderGateway;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<HydrationDayDataModel>
 */
final class HydrationDayRepository extends ServiceEntityRepository implements HydrationDayProviderGateway
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HydrationDayDataModel::class);
    }

    public function findOneForOwnerAndDay(UserDataModel $owner, DateTimeImmutable $day): ?HydrationDayDataModel
    {
        // The entries are what the caller came for: the total is read off them, and so is the
        // list shown in the window.
        return $this->createQueryBuilder('hydrationDay')
            ->leftJoin('hydrationDay.entries', 'entry')
            ->addSelect('entry')
            ->andWhere('hydrationDay.owner = :owner')
            ->andWhere('hydrationDay.day = :day')
            ->setParameter('owner', $owner)
            ->setParameter('day', $day)
            ->orderBy('entry.recordedAt', 'DESC')
            ->getQuery()
            ->getOneOrNullResult();
    }
}
