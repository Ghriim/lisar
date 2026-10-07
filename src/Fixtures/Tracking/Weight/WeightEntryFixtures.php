<?php

declare(strict_types=1);

namespace App\Fixtures\Tracking\Weight;

use App\Domain\DTO\DataModel\Tracking\WeightEntryDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Gateway\Persister\Tracking\Weight\WeightEntryPersisterGateway;
use App\Domain\Tracking\DayClock;
use App\Fixtures\User\UserFixtures;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * A weight on a past day, deliberately not today.
 *
 * It seeds the case the widget exists for — the last known weight is not today's — which cannot
 * be produced through the API at all, since no request can name a day.
 */
final class WeightEntryFixtures extends Fixture implements DependentFixtureInterface
{
    public const string ALICE_PAST = 'weight-entry-alice-past';

    /** How far back the seeded measurement sits. */
    public const int DAYS_AGO = 3;

    public const float ALICE_WEIGHT_IN_KILOGRAMS = 72.4;

    public function __construct(
        private readonly WeightEntryPersisterGateway $weightEntryPersisterGateway,
        private readonly DayClock $clock,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $alice = $this->getReference(UserFixtures::ALICE, UserDataModel::class);

        $day = $this->clock->today()->modify(sprintf('-%d days', self::DAYS_AGO));

        $entry = new WeightEntryDataModel();
        $entry->owner = $alice;
        $entry->day = $day;
        // Early morning, the hour someone actually weighs themselves. The day is carried in the
        // display timezone, so the moment it builds has to be stored as the instant it is.
        $entry->recordedAt = $this->clock->asStoredInstant($day->setTime(7, 12));
        $entry->weightInKilograms = self::ALICE_WEIGHT_IN_KILOGRAMS;

        $this->weightEntryPersisterGateway->create($entry);

        $this->addReference(self::ALICE_PAST, $entry);
    }

    /** @return list<class-string> */
    public function getDependencies(): array
    {
        return [UserFixtures::class];
    }
}
