<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\DTO\DataModel\MuscleGroupDataModel;
use App\Domain\Gateway\Provider\MuscleGroupProviderGateway;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MuscleGroupDataModel>
 */
final class MuscleGroupRepository extends ServiceEntityRepository implements MuscleGroupProviderGateway
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MuscleGroupDataModel::class);
    }

    public function findOneById(int $id): ?MuscleGroupDataModel
    {
        return $this->createQueryBuilder('muscleGroup')
            ->andWhere('muscleGroup.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneByName(string $name): ?MuscleGroupDataModel
    {
        return $this->createQueryBuilder('muscleGroup')
            ->andWhere('muscleGroup.name = :name')
            ->setParameter('name', $name)
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult();
    }

    /** @return list<MuscleGroupDataModel> */
    public function findAllForAdminList(): array
    {
        return $this->createQueryBuilder('muscleGroup')
            ->orderBy('muscleGroup.name', 'ASC')
            ->addOrderBy('muscleGroup.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
