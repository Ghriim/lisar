<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\DTO\DataModel\HabitDataModel;
use App\Domain\Gateway\Provider\HabitProviderGateway;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<HabitDataModel>
 */
final class HabitRepository extends ServiceEntityRepository implements HabitProviderGateway
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HabitDataModel::class);
    }

    public function findOneById(int $id): ?HabitDataModel
    {
        return $this->createQueryBuilder('habit')
            ->andWhere('habit.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return list<HabitDataModel> */
    public function findAllForAdminList(): array
    {
        return $this->createQueryBuilder('habit')
            ->orderBy('habit.createdAt', 'DESC')
            ->addOrderBy('habit.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return list<HabitDataModel> */
    public function findAllActive(): array
    {
        return $this->createQueryBuilder('habit')
            ->andWhere('habit.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('habit.name', 'ASC')
            ->addOrderBy('habit.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
