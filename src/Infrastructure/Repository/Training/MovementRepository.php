<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository\Training;

use App\Domain\DTO\DataModel\Training\EquipmentDataModel;
use App\Domain\DTO\DataModel\Training\MovementDataModel;
use App\Domain\DTO\DataModel\Training\MovementFamilyDataModel;
use App\Domain\DTO\DataModel\Training\MuscleDataModel;
use App\Domain\Gateway\Provider\Training\MovementProviderGateway;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MovementDataModel>
 */
final class MovementRepository extends ServiceEntityRepository implements MovementProviderGateway
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MovementDataModel::class);
    }

    public function findOneCommonById(int $id): ?MovementDataModel
    {
        return $this->common()
            ->andWhere('movement.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneCommonByName(string $name): ?MovementDataModel
    {
        // No joins: this only answers whether the name is taken.
        return $this->createQueryBuilder('movement')
            ->andWhere('movement.owner IS NULL')
            ->andWhere('movement.name = :name')
            ->setParameter('name', $name)
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult();
    }

    /** @return list<MovementDataModel> */
    public function findAllCommonForAdminList(
        ?bool $isActive,
        ?int $movementFamilyId,
        ?int $muscleGroupId,
        ?int $muscleId,
        ?int $equipmentId,
    ): array {
        $queryBuilder = $this->common()
            ->orderBy('movement.name', 'ASC')
            ->addOrderBy('movement.id', 'ASC');

        if (null !== $isActive) {
            $queryBuilder->andWhere('movement.isActive = :active')->setParameter('active', $isActive);
        }
        if (null !== $movementFamilyId) {
            $queryBuilder->andWhere('movementFamily.id = :movementFamilyId')->setParameter('movementFamilyId', $movementFamilyId);
        }

        // The muscle and equipment filters go through subqueries rather than the joins above:
        // narrowing a fetch-joined collection would hand back movements with half their muscles.
        if (null !== $muscleId) {
            $queryBuilder
                ->andWhere(sprintf('movement.id IN (%s)', $this->targeting('muscleFilter', 'filteredMuscle', 'filteredSecondary')
                    ->andWhere('filteredMuscle.id = :muscleId OR filteredSecondary.id = :muscleId')
                    ->getDQL()))
                ->setParameter('muscleId', $muscleId);
        }
        if (null !== $muscleGroupId) {
            $queryBuilder
                ->andWhere(sprintf('movement.id IN (%s)', $this->targeting('groupFilter', 'groupPrimary', 'groupSecondary')
                    ->andWhere('groupPrimary.muscleGroup = :muscleGroupId OR groupSecondary.muscleGroup = :muscleGroupId')
                    ->getDQL()))
                ->setParameter('muscleGroupId', $muscleGroupId);
        }
        if (null !== $equipmentId) {
            $queryBuilder
                ->andWhere(sprintf('movement.id IN (%s)', $this->createQueryBuilder('equipmentFilter')
                    ->select('equipmentFilter.id')
                    ->innerJoin('equipmentFilter.equipments', 'filteredEquipment')
                    ->andWhere('filteredEquipment.id = :equipmentId')
                    ->getDQL()))
                ->setParameter('equipmentId', $equipmentId);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    public function findOneOfferedById(int $id): ?MovementDataModel
    {
        return $this->offered()
            ->andWhere('movement.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return list<MovementDataModel> */
    public function findAllOffered(): array
    {
        return $this->offered()
            ->orderBy('movement.name', 'ASC')
            ->addOrderBy('movement.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countForMovementFamily(MovementFamilyDataModel $movementFamily): int
    {
        return (int) $this->createQueryBuilder('movement')
            ->select('COUNT(movement.id)')
            ->andWhere('movement.movementFamily = :movementFamily')
            ->setParameter('movementFamily', $movementFamily)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countForMuscle(MuscleDataModel $muscle): int
    {
        return (int) $this->createQueryBuilder('movement')
            ->select('COUNT(DISTINCT movement.id)')
            ->leftJoin('movement.secondaryMuscles', 'secondary')
            ->andWhere('movement.primaryMuscle = :muscle OR secondary.id = :muscleId')
            ->setParameter('muscle', $muscle)
            ->setParameter('muscleId', $muscle->id)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countForEquipment(EquipmentDataModel $equipment): int
    {
        return (int) $this->createQueryBuilder('movement')
            ->select('COUNT(DISTINCT movement.id)')
            ->innerJoin('movement.equipments', 'equipment')
            ->andWhere('equipment.id = :equipmentId')
            ->setParameter('equipmentId', $equipment->id)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** Every read that feeds an output: all of it joined and selected, the common ones only. */
    private function common(): QueryBuilder
    {
        return $this->createQueryBuilder('movement')
            ->innerJoin('movement.movementFamily', 'movementFamily')
            ->addSelect('movementFamily')
            ->innerJoin('movement.primaryMuscle', 'primaryMuscle')
            ->addSelect('primaryMuscle')
            ->innerJoin('primaryMuscle.muscleGroup', 'primaryMuscleGroup')
            ->addSelect('primaryMuscleGroup')
            ->leftJoin('movement.secondaryMuscles', 'secondaryMuscle')
            ->addSelect('secondaryMuscle')
            ->leftJoin('secondaryMuscle.muscleGroup', 'secondaryMuscleGroup')
            ->addSelect('secondaryMuscleGroup')
            ->leftJoin('movement.equipments', 'equipment')
            ->addSelect('equipment')
            ->andWhere('movement.owner IS NULL');
    }

    private function offered(): QueryBuilder
    {
        return $this->common()
            ->andWhere('movement.isActive = true')
            ->andWhere('movementFamily.isActive = true');
    }

    /** The ids of the movements, with their primary and secondary muscles joined under these aliases. */
    private function targeting(string $alias, string $primaryAlias, string $secondaryAlias): QueryBuilder
    {
        return $this->createQueryBuilder($alias)
            ->select($alias.'.id')
            ->innerJoin($alias.'.primaryMuscle', $primaryAlias)
            ->leftJoin($alias.'.secondaryMuscles', $secondaryAlias);
    }
}
