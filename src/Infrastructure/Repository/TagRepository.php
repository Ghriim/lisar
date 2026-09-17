<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\DTO\DataModel\TagDataModel;
use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\Gateway\Provider\TagProviderGateway;
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
