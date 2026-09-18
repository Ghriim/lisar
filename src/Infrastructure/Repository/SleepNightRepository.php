<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\DTO\DataModel\SleepNightDataModel;
use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\Gateway\Provider\SleepNightProviderGateway;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SleepNightDataModel>
 */
final class SleepNightRepository extends ServiceEntityRepository implements SleepNightProviderGateway
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SleepNightDataModel::class);
    }

    public function findOneForOwnerAndDay(UserDataModel $owner, DateTimeImmutable $day): ?SleepNightDataModel
    {
        return $this->createQueryBuilder('sleepNight')
            ->andWhere('sleepNight.owner = :owner')
            ->andWhere('sleepNight.day = :day')
            ->setParameter('owner', $owner)
            ->setParameter('day', $day)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
