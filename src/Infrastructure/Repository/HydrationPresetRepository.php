<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\DTO\DataModel\HydrationPresetDataModel;
use App\Domain\Gateway\Provider\HydrationPresetProviderGateway;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<HydrationPresetDataModel>
 */
final class HydrationPresetRepository extends ServiceEntityRepository implements HydrationPresetProviderGateway
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HydrationPresetDataModel::class);
    }

    public function findOneById(int $id): ?HydrationPresetDataModel
    {
        return $this->createQueryBuilder('preset')
            ->andWhere('preset.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return list<HydrationPresetDataModel> */
    public function findAllOrderedByVolume(): array
    {
        return $this->createQueryBuilder('preset')
            ->orderBy('preset.volumeInMillilitres', 'ASC')
            ->addOrderBy('preset.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
