<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository\Gamification;

use App\Domain\DTO\DataModel\Gamification\ExperienceGainDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Gateway\Provider\Gamification\ExperienceGainProviderGateway;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ExperienceGainDataModel>
 */
final class ExperienceGainRepository extends ServiceEntityRepository implements ExperienceGainProviderGateway
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExperienceGainDataModel::class);
    }

    /** @return array<string, int> */
    public function sumByStatForOwner(UserDataModel $owner): array
    {
        $rows = $this->createQueryBuilder('gain')
            ->select('gain.stat AS stat', 'SUM(gain.amount) AS experience')
            ->andWhere('gain.owner = :owner')
            ->setParameter('owner', $owner)
            ->groupBy('gain.stat')
            ->getQuery()
            ->getArrayResult();

        $sums = [];
        foreach ($rows as $row) {
            $sums[(string) $row['stat']] = (int) $row['experience'];
        }

        return $sums;
    }
}
