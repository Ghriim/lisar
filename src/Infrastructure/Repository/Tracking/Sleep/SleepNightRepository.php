<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository\Tracking\Sleep;

use App\Domain\DTO\DataModel\Tracking\SleepNightDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Gateway\Provider\Tracking\Sleep\SleepNightProviderGateway;
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
