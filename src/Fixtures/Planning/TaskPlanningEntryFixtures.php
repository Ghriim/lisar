<?php

declare(strict_types=1);

namespace App\Fixtures\Planning;

use App\Domain\DTO\DataModel\Category\CategoryDataModel;
use App\Domain\DTO\DataModel\Todo\TodoDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Planning\PlanningScheduleDataInput;
use App\Domain\DTO\Input\Planning\SaveTaskPlanningEntryDataInput;
use App\Domain\Factory\DataModelFactory\Planning\TaskPlanningEntryDataModelFactory;
use App\Domain\Gateway\Persister\Planning\TaskPlanningEntryPersisterGateway;
use App\Domain\Registry\Planning\PlanningRuleRegistry;
use App\Fixtures\Category\CategoryFixtures;
use App\Fixtures\Todo\TodoFixtures;
use App\Fixtures\User\UserFixtures;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Alice takes the bins out on Mondays and Thursdays at 20:00, from 2 November 2026, filed under
 * « Home », in her « A faire ». Created through the persister, so today's task is made if today is one of those.
 */
final class TaskPlanningEntryFixtures extends Fixture implements DependentFixtureInterface
{
    public const string ALICE_BINS_MONDAY_THURSDAY = 'task-planning-alice-bins';

    public function __construct(
        private readonly TaskPlanningEntryDataModelFactory $entryFactory,
        private readonly TaskPlanningEntryPersisterGateway $entryPersisterGateway,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $entry = $this->entryFactory->build(
            $this->getReference(UserFixtures::ALICE, UserDataModel::class),
            $this->getReference(TodoFixtures::ALICE_DEFAULT, TodoDataModel::class),
            $this->getReference(CategoryFixtures::HOME, CategoryDataModel::class),
            new SaveTaskPlanningEntryDataInput(
                title: 'Sortir les poubelles',
                description: 'Jaune le lundi, verte le jeudi.',
                schedule: new PlanningScheduleDataInput(
                    rule: PlanningRuleRegistry::WEEKLY,
                    startDate: '2026-11-02',
                    weekdays: [1, 4],
                    startTime: '20:00',
                ),
            ),
        );

        $this->entryPersisterGateway->create($entry);
        $this->addReference(self::ALICE_BINS_MONDAY_THURSDAY, $entry);
    }

    /** @return list<class-string> */
    public function getDependencies(): array
    {
        return [UserFixtures::class, CategoryFixtures::class, TodoFixtures::class];
    }
}
