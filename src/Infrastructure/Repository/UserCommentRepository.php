<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\DTO\DataModel\UserCommentDataModel;
use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\Gateway\Provider\UserCommentProviderGateway;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserCommentDataModel>
 */
final class UserCommentRepository extends ServiceEntityRepository implements UserCommentProviderGateway
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserCommentDataModel::class);
    }

    /** @return list<UserCommentDataModel> */
    public function findAllForUser(UserDataModel $user): array
    {
        // The author is read downstream, to show who wrote each note.
        return $this->createQueryBuilder('userComment')
            ->innerJoin('userComment.author', 'author')
            ->addSelect('author')
            ->andWhere('userComment.user = :user')
            ->setParameter('user', $user)
            ->orderBy('userComment.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
