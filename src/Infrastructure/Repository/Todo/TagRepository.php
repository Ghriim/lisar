<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository\Todo;

use App\Domain\DTO\DataModel\Todo\TagDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Gateway\Provider\Todo\TagProviderGateway;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TagDataModel>
 */
final class TagRepository extends ServiceEntityRepository implements TagProviderGateway
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TagDataModel::class);
    }

    /** @return list<TagDataModel> */
    public function findAllForOwner(UserDataModel $owner): array
    {
        return $this->createQueryBuilder('tag')
            ->andWhere('tag.owner = :owner')
            ->setParameter('owner', $owner)
            ->orderBy('tag.label', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param list<string> $labels
     *
     * @return list<TagDataModel>
     */
    public function findAllByLabelsForOwner(UserDataModel $owner, array $labels): array
    {
        if ([] === $labels) {
            return [];
        }

        return $this->createQueryBuilder('tag')
            ->andWhere('tag.owner = :owner')
            ->andWhere('tag.label IN (:labels)')
            ->setParameter('owner', $owner)
            ->setParameter('labels', $labels)
            ->getQuery()
            ->getResult();
    }
}
