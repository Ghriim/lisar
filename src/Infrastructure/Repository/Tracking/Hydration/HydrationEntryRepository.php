<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository\Tracking\Hydration;

use App\Domain\DTO\DataModel\Tracking\HydrationEntryDataModel;
use App\Domain\Gateway\Provider\Tracking\Hydration\HydrationEntryProviderGateway;
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

    public function findOneById(int $id): ?HydrationEntryDataModel
    {
        // The day is read downstream: whose it is, and whether it is the one in progress.
        return $this->createQueryBuilder('entry')
            ->innerJoin('entry.hydrationDay', 'hydrationDay')
            ->innerJoin('hydrationDay.owner', 'owner')
            ->addSelect('hydrationDay', 'owner')
            ->andWhere('entry.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
