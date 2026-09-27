<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\DTO\DataModel\MuscleDataModel;
use App\Domain\DTO\DataModel\MuscleGroupDataModel;
use App\Domain\Gateway\Provider\MuscleProviderGateway;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MuscleDataModel>
 */
final class MuscleRepository extends ServiceEntityRepository implements MuscleProviderGateway
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MuscleDataModel::class);
    }

    public function findOneById(int $id): ?MuscleDataModel
    {
        return $this->withGroup()
            ->andWhere('muscle.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneByName(string $name): ?MuscleDataModel
    {
        return $this->withGroup()
            ->andWhere('muscle.name = :name')
            ->setParameter('name', $name)
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult();
    }

    /** @return list<MuscleDataModel> */
    public function findAllForAdminList(?bool $isActive, ?int $muscleGroupId): array
    {
        $queryBuilder = $this->withGroup()
            ->orderBy('muscleGroup.name', 'ASC')
            ->addOrderBy('muscle.name', 'ASC')
            ->addOrderBy('muscle.id', 'ASC');

        if (null !== $isActive) {
            $queryBuilder->andWhere('muscle.isActive = :active')->setParameter('active', $isActive);
        }
        if (null !== $muscleGroupId) {
            $queryBuilder->andWhere('muscleGroup.id = :muscleGroupId')->setParameter('muscleGroupId', $muscleGroupId);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    public function countForMuscleGroup(MuscleGroupDataModel $muscleGroup): int
    {
        return (int) $this->createQueryBuilder('muscle')
            ->select('COUNT(muscle.id)')
            ->andWhere('muscle.muscleGroup = :muscleGroup')
            ->setParameter('muscleGroup', $muscleGroup)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** Every read here hands the group on to the output, so it is always joined and selected. */
    private function withGroup(): QueryBuilder
    {
        return $this->createQueryBuilder('muscle')
            ->innerJoin('muscle.muscleGroup', 'muscleGroup')
            ->addSelect('muscleGroup');
    }
}
