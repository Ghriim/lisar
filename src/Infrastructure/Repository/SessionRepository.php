<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\DTO\DataModel\SessionDataModel;
use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\Gateway\Provider\SessionProviderGateway;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SessionDataModel>
 */
final class SessionRepository extends ServiceEntityRepository implements SessionProviderGateway
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SessionDataModel::class);
    }

    public function findOneByRefreshTokenHash(string $refreshTokenHash): ?SessionDataModel
    {
        // The account and its identities are read downstream, to mint the next access token.
        return $this->createQueryBuilder('session')
            ->innerJoin('session.user', 'user')
            ->addSelect('user')
            ->leftJoin('user.identities', 'identity')
            ->addSelect('identity')
            ->andWhere('session.refreshTokenHash = :refreshTokenHash')
            ->setParameter('refreshTokenHash', $refreshTokenHash)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return list<SessionDataModel> */
    public function findAllLiveForUser(UserDataModel $user): array
    {
        return $this->createQueryBuilder('session')
            ->andWhere('session.user = :user')
            ->andWhere('session.revokedAt IS NULL')
            ->setParameter('user', $user)
            ->orderBy('session.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
