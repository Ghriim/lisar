<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository\Tracking\Hydration;

use App\Domain\DTO\DataModel\Tracking\HydrationDayDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Gateway\Provider\Tracking\Hydration\HydrationDayProviderGateway;
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
