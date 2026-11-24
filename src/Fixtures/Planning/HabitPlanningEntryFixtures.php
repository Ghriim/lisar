<?php

declare(strict_types=1);

namespace App\Fixtures\Planning;

use App\Domain\DTO\DataModel\Habits\HabitSubscriptionDataModel;
use App\Domain\DTO\Input\Planning\PlanningScheduleDataInput;
use App\Domain\Factory\DataModelFactory\Planning\HabitPlanningEntryDataModelFactory;
use App\Domain\Gateway\Persister\Planning\HabitPlanningEntryPersisterGateway;
use App\Domain\Registry\Planning\PlanningRuleRegistry;
use App\Fixtures\Habits\HabitSubscriptionFixtures;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Alice reads on Mondays, Wednesdays and Fridays, from 2 November 2026. Her walk has no entry, and
 * counts every day.
 */
final class HabitPlanningEntryFixtures extends Fixture implements DependentFixtureInterface
{
    public const string ALICE_READING_MONDAY_WEDNESDAY_FRIDAY = 'habit-planning-alice-reading';

    public function __construct(
        private readonly HabitPlanningEntryDataModelFactory $entryFactory,
        private readonly HabitPlanningEntryPersisterGateway $entryPersisterGateway,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $entry = $this->entryFactory->build(
            $this->getReference(HabitSubscriptionFixtures::ALICE_READING, HabitSubscriptionDataModel::class),
            new PlanningScheduleDataInput(
                rule: PlanningRuleRegistry::WEEKLY,
                startDate: '2026-11-02',
                weekdays: [1, 3, 5],
            ),
        );

        $this->entryPersisterGateway->create($entry);
        $this->addReference(self::ALICE_READING_MONDAY_WEDNESDAY_FRIDAY, $entry);
    }

    /** @return list<class-string> */
    public function getDependencies(): array
    {
        return [HabitSubscriptionFixtures::class];
    }
}
