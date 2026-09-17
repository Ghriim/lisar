<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\DTO\DataModel\CategoryDataModel;
use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\Gateway\Provider\CategoryProviderGateway;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CategoryDataModel>
 */
final class CategoryRepository extends ServiceEntityRepository implements CategoryProviderGateway
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CategoryDataModel::class);
    }

    public function findOneById(int $id): ?CategoryDataModel
    {
        return $this->createQueryBuilder('category')
            ->leftJoin('category.owner', 'owner')
            ->addSelect('owner')
            ->andWhere('category.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return list<CategoryDataModel> */
    public function findAllUsableBy(UserDataModel $user): array
    {
        return $this->createQueryBuilder('category')
            ->leftJoin('category.owner', 'owner')
            ->addSelect('owner')
            ->andWhere('category.owner IS NULL OR category.owner = :user')
            ->setParameter('user', $user)
            ->orderBy('category.label', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return list<CategoryDataModel> */
    public function findAllReference(): array
    {
        return $this->createQueryBuilder('category')
            ->andWhere('category.owner IS NULL')
            ->orderBy('category.label', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByLabelForOwner(string $label, ?UserDataModel $owner): ?CategoryDataModel
    {
        $queryBuilder = $this->createQueryBuilder('category')
            ->andWhere('category.label = :label')
            ->setParameter('label', $label);

        if (null === $owner) {
            $queryBuilder->andWhere('category.owner IS NULL');
        } else {
            $queryBuilder->andWhere('category.owner = :owner')->setParameter('owner', $owner);
        }

        return $queryBuilder->getQuery()->setMaxResults(1)->getOneOrNullResult();
    }
}
