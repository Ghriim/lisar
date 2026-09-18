<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\DTO\DataModel\HydrationEntryDataModel;
use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\Gateway\Provider\HydrationEntryProviderGateway;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<HydrationEntryDataModel>
 */
final class HydrationEntryRepository extends ServiceEntityRepository implements HydrationEntryProviderGateway
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HydrationEntryDataModel::class);
    }

    public function findOneByIdForOwner(int $id, UserDataModel $owner): ?HydrationEntryDataModel
    {
        // The day is read downstream, to check the entry belongs to the one in progress.
        return $this->createQueryBuilder('entry')
            ->innerJoin('entry.hydrationDay', 'hydrationDay')
            ->addSelect('hydrationDay')
            ->andWhere('entry.id = :id')
            ->andWhere('hydrationDay.owner = :owner')
            ->setParameter('id', $id)
            ->setParameter('owner', $owner)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
