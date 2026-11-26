<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository\Habits;

use App\Domain\DTO\DataModel\Category\CategoryDataModel;
use App\Domain\DTO\DataModel\Habits\HabitDataModel;
use App\Domain\Gateway\Provider\Habits\HabitProviderGateway;
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
            // The category is drawn with the habit: joined here, never fetched one habit at a time.
            ->leftJoin('habit.category', 'category')
            ->addSelect('category')
            ->andWhere('habit.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return list<HabitDataModel> */
    public function findAllForAdminList(?bool $isActive): array
    {
        $queryBuilder = $this->createQueryBuilder('habit')
            // The category is drawn with the habit: joined here, never fetched one habit at a time.
            ->leftJoin('habit.category', 'category')
            ->addSelect('category')
            ->orderBy('habit.name', 'ASC')
            ->addOrderBy('habit.id', 'ASC');

        if (null !== $isActive) {
            $queryBuilder->andWhere('habit.isActive = :active')->setParameter('active', $isActive);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /** @return list<HabitDataModel> */
    public function findAllActive(): array
    {
        return $this->createQueryBuilder('habit')
            // The category is drawn with the habit: joined here, never fetched one habit at a time.
            ->leftJoin('habit.category', 'category')
            ->addSelect('category')
            ->andWhere('habit.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('habit.name', 'ASC')
            ->addOrderBy('habit.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countForCategory(CategoryDataModel $category): int
    {
        return (int) $this->createQueryBuilder('habit')
            ->select('COUNT(habit.id)')
            ->andWhere('habit.category = :category')
            ->setParameter('category', $category)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
