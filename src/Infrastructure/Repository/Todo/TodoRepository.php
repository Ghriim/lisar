<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository\Todo;

use App\Domain\DTO\DataModel\Todo\TodoDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Gateway\Provider\Todo\TodoProviderGateway;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TodoDataModel>
 */
final class TodoRepository extends ServiceEntityRepository implements TodoProviderGateway
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TodoDataModel::class);
    }

    public function findOneById(int $id): ?TodoDataModel
    {
        return $this->createQueryBuilder('todo')
            ->innerJoin('todo.owner', 'owner')
            ->addSelect('owner')
            ->andWhere('todo.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findDefaultForOwner(UserDataModel $owner): ?TodoDataModel
    {
        return $this->createQueryBuilder('todo')
            ->andWhere('todo.owner = :owner')
            ->andWhere('todo.isDefault = true')
            ->setParameter('owner', $owner)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return list<TodoDataModel> */
    public function findAllForOwner(UserDataModel $owner, ?string $status): array
    {
        $queryBuilder = $this->createQueryBuilder('todo')
            ->andWhere('todo.owner = :owner')
            ->setParameter('owner', $owner);

        if (null !== $status) {
            $queryBuilder->andWhere('todo.status = :status')->setParameter('status', $status);
        }

        return $queryBuilder
            ->addOrderBy('todo.isDefault', 'DESC')
            ->addOrderBy('todo.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByTitleForOwner(string $title, UserDataModel $owner): ?TodoDataModel
    {
        return $this->createQueryBuilder('todo')
            ->andWhere('todo.owner = :owner')
            ->andWhere('todo.title = :title')
            ->setParameter('owner', $owner)
            ->setParameter('title', $title)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
