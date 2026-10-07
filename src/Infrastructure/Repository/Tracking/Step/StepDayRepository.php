<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository\Tracking\Step;

use App\Domain\DTO\DataModel\Tracking\StepDayDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Gateway\Provider\Tracking\Step\StepDayProviderGateway;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StepDayDataModel>
 */
final class StepDayRepository extends ServiceEntityRepository implements StepDayProviderGateway
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StepDayDataModel::class);
    }

    public function findOneForOwnerAndDay(UserDataModel $owner, DateTimeImmutable $day): ?StepDayDataModel
    {
        return $this->createQueryBuilder('stepDay')
            ->andWhere('stepDay.owner = :owner')
            ->andWhere('stepDay.day = :day')
            ->setParameter('owner', $owner)
            ->setParameter('day', $day)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
