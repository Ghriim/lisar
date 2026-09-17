<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\Gateway\Provider\UserProviderGateway;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

use function sprintf;

/**
 * @extends ServiceEntityRepository<UserDataModel>
 */
final class UserRepository extends ServiceEntityRepository implements UserProviderGateway
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserDataModel::class);
    }

    public function findOneById(int $id): ?UserDataModel
    {
        return $this->createQueryBuilder('user')
            ->leftJoin('user.identities', 'identity')
            ->addSelect('identity')
            ->andWhere('user.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneByEmail(string $email): ?UserDataModel
    {
        return $this->createQueryBuilder('user')
            ->leftJoin('user.identities', 'identity')
            ->addSelect('identity')
            ->andWhere('user.email = :email')
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return list<UserDataModel> */
    public function findAllForAdminList(?string $search, ?bool $isActive, int $limit, int $offset): array
    {
        return $this->buildAdminListQuery($search, $isActive)
            ->orderBy('user.id', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function countAllForAdminList(?string $search, ?bool $isActive): int
    {
        return (int) $this->buildAdminListQuery($search, $isActive)
            ->select('COUNT(user.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function buildAdminListQuery(?string $search, ?bool $isActive): QueryBuilder
    {
        // No join on the identities here: the list shows account metadata only, never how an
        // account signs in.
        $queryBuilder = $this->createQueryBuilder('user');

        if (null !== $search && '' !== $search) {
            $queryBuilder
                ->andWhere('user.username LIKE :search OR user.email LIKE :search')
                ->setParameter('search', sprintf('%%%s%%', $search));
        }

        if (null !== $isActive) {
            $queryBuilder
                ->andWhere('user.isActive = :isActive')
                ->setParameter('isActive', $isActive);
        }

        return $queryBuilder;
    }

    public function findOneByUsername(string $username): ?UserDataModel
    {
        return $this->createQueryBuilder('user')
            ->leftJoin('user.identities', 'identity')
            ->addSelect('identity')
            ->andWhere('user.username = :username')
            ->setParameter('username', $username)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
