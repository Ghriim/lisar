<?php

declare(strict_types=1);

namespace App\Fixtures\Gamification;

use App\Domain\DTO\DataModel\Gamification\ExperienceGainDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Gateway\Persister\Gamification\ExperienceGainPersisterGateway;
use App\Domain\Registry\Gamification\StatRegistry;
use App\Fixtures\User\UserFixtures;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Some experience for Alice, until the gains are wired, so that her Statut has something to show:
 * Force 320 (level 3), Endurance 60 (level 2), Intelligence 40 (level 1) — 420 in all (level 3).
 * Bob has none.
 */
final class ExperienceGainFixtures extends Fixture implements DependentFixtureInterface
{
    /** stat, amount */
    private const array ALICE = [
        [StatRegistry::STRENGTH, 200],
        [StatRegistry::STRENGTH, 120],
        [StatRegistry::ENDURANCE, 60],
        [StatRegistry::INTELLIGENCE, 40],
    ];

    public function __construct(private readonly ExperienceGainPersisterGateway $experienceGainPersisterGateway)
    {
    }

    /** @return list<class-string> */
    public function getDependencies(): array
    {
        return [UserFixtures::class];
    }

    public function load(ObjectManager $manager): void
    {
        $alice = $this->getReference(UserFixtures::ALICE, UserDataModel::class);

        foreach (self::ALICE as [$stat, $amount]) {
            $gain = new ExperienceGainDataModel();
            $gain->owner = $alice;
            $gain->stat = $stat;
            $gain->amount = $amount;
            $gain->earnedAt = new DateTimeImmutable('2026-11-20T08:00:00Z');
            $this->experienceGainPersisterGateway->create($gain);
        }
    }
}
