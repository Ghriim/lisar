<?php

declare(strict_types=1);

namespace App\Fixtures;

use App\Domain\DTO\DataModel\StepDayDataModel;
use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\Gateway\Persister\StepDayPersisterGateway;
use App\Domain\Registry\Step\StepSourceRegistry;
use App\Domain\Tracking\DayClock;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

use function sprintf;

/**
 * A step count on a past day, deliberately not today.
 *
 * It seeds a day the widget will never write to — no request can name a day — so the tracker has
 * history to read the moment the statistics domain arrives, and so a test has a past row to prove
 * a second save corrects today's rather than adding a third.
 */
final class StepDayFixtures extends Fixture implements DependentFixtureInterface
{
    public const string ALICE_PAST = 'step-day-alice-past';

    /** How far back the seeded day sits. */
    public const int DAYS_AGO = 2;

    public const int ALICE_COUNT_IN_STEPS = 8000;

    public function __construct(
        private readonly StepDayPersisterGateway $stepDayPersisterGateway,
        private readonly DayClock $clock,
        #[Autowire('%step_daily_goal%')]
        private readonly int $defaultGoalInSteps,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $alice = $this->getReference(UserFixtures::ALICE, UserDataModel::class);

        $day = $this->clock->today()->modify(sprintf('-%d days', self::DAYS_AGO));

        $stepDay = new StepDayDataModel();
        $stepDay->owner = $alice;
        $stepDay->day = $day;
        $stepDay->goalInSteps = $this->defaultGoalInSteps;
        $stepDay->countInSteps = self::ALICE_COUNT_IN_STEPS;
        $stepDay->source = StepSourceRegistry::MANUAL;

        $this->stepDayPersisterGateway->create($stepDay);

        $this->addReference(self::ALICE_PAST, $stepDay);
    }

    /** @return list<class-string> */
    public function getDependencies(): array
    {
        return [UserFixtures::class];
    }
}
