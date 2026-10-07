<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository\Training;

use App\Domain\DTO\DataModel\Training\SetTypeDataModel;
use App\Domain\Gateway\Provider\Training\SetTypeProviderGateway;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SetTypeDataModel>
 */
final class SetTypeRepository extends ServiceEntityRepository implements SetTypeProviderGateway
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SetTypeDataModel::class);
    }

    public function findOneById(int $id): ?SetTypeDataModel
    {
        return $this->createQueryBuilder('setType')
            ->andWhere('setType.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneByName(string $name): ?SetTypeDataModel
    {
        return $this->createQueryBuilder('setType')
            ->andWhere('setType.name = :name')
            ->setParameter('name', $name)
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult();
    }

    public function findOneDefault(): ?SetTypeDataModel
    {
        return $this->createQueryBuilder('setType')
            ->andWhere('setType.isDefaultType = true')
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult();
    }

    /** @return list<SetTypeDataModel> */
    public function findAllForAdminList(?bool $isActive): array
    {
        $queryBuilder = $this->createQueryBuilder('setType')
            ->orderBy('setType.name', 'ASC')
            ->addOrderBy('setType.id', 'ASC');

        if (null !== $isActive) {
            $queryBuilder->andWhere('setType.isActive = :active')->setParameter('active', $isActive);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /** @return list<SetTypeDataModel> */
    public function findAllActive(): array
    {
        return $this->findAllForAdminList(true);
    }
}
