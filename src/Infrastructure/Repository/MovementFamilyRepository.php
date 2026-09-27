<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\DTO\DataModel\MovementFamilyDataModel;
use App\Domain\Gateway\Provider\MovementFamilyProviderGateway;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MovementFamilyDataModel>
 */
final class MovementFamilyRepository extends ServiceEntityRepository implements MovementFamilyProviderGateway
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MovementFamilyDataModel::class);
    }

    public function findOneById(int $id): ?MovementFamilyDataModel
    {
        return $this->createQueryBuilder('movementFamily')
            ->andWhere('movementFamily.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneByName(string $name): ?MovementFamilyDataModel
    {
        return $this->createQueryBuilder('movementFamily')
            ->andWhere('movementFamily.name = :name')
            ->setParameter('name', $name)
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult();
    }

    /** @return list<MovementFamilyDataModel> */
    public function findAllForAdminList(): array
    {
        return $this->createQueryBuilder('movementFamily')
            ->orderBy('movementFamily.name', 'ASC')
            ->addOrderBy('movementFamily.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
