<?php

declare(strict_types=1);

namespace App\Fixtures;

use App\Domain\DTO\DataModel\SleepNightDataModel;
use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\Gateway\Persister\SleepNightPersisterGateway;
use App\Domain\Registry\Sleep\SleepMoodRegistry;
use App\Domain\Tracking\DayClock;
use App\Domain\Tracking\SleepWindow;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

use function sprintf;

/**
 * A night on a past day, deliberately not today.
 *
 * It seeds what no request can produce — no route names a day — and it is what proves the
 * widget's rule: the night of another day is not shown, however recent it is.
 */
final class SleepNightFixtures extends Fixture implements DependentFixtureInterface
{
    public const string ALICE_PAST = 'sleep-night-alice-past';

    /** How far back the seeded night sits. */
    public const int DAYS_AGO = 2;

    /** Minutes since midnight: to bed at 23:30, up at 07:00 — a night across midnight. */
    public const int BEDTIME_IN_MINUTES = (23 * 60) + 30;
    public const int WAKE_UP_IN_MINUTES = 7 * 60;

    public const int MOOD_RATING = SleepMoodRegistry::BEST - 1;

    public function __construct(
        private readonly SleepNightPersisterGateway $sleepNightPersisterGateway,
        private readonly DayClock $clock,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $alice = $this->getReference(UserFixtures::ALICE, UserDataModel::class);

        $wakingDay = $this->clock->today()->modify(sprintf('-%d days', self::DAYS_AGO));
        $window = SleepWindow::forWakingDay($wakingDay, self::BEDTIME_IN_MINUTES, self::WAKE_UP_IN_MINUTES);

        $night = new SleepNightDataModel();
        $night->owner = $alice;
        $night->day = $wakingDay;
        $night->bedtimeAt = $this->clock->asStoredInstant($window->bedtimeAt);
        $night->wakeUpAt = $this->clock->asStoredInstant($window->wakeUpAt);
        $night->moodRating = self::MOOD_RATING;

        $this->sleepNightPersisterGateway->create($night);

        $this->addReference(self::ALICE_PAST, $night);
    }

    /** @return list<class-string> */
    public function getDependencies(): array
    {
        return [UserFixtures::class];
    }
}
