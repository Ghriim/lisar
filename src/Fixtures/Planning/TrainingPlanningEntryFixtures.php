<?php

declare(strict_types=1);

namespace App\Fixtures\Planning;

use App\Domain\DTO\DataModel\Training\RoutineDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Planning\PlanningScheduleDataInput;
use App\Domain\Factory\DataModelFactory\Planning\TrainingPlanningEntryDataModelFactory;
use App\Domain\Gateway\Persister\Planning\TrainingPlanningEntryPersisterGateway;
use App\Domain\Registry\Planning\PlanningRuleRegistry;
use App\Fixtures\Training\RoutineFixtures;
use App\Fixtures\User\UserFixtures;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/** Alice's « Push », planned on Tuesdays and Thursdays at 18:00 for 1 h 15, from 2 November 2026. */
final class TrainingPlanningEntryFixtures extends Fixture implements DependentFixtureInterface
{
    public const string ALICE_PUSH_TUESDAY_THURSDAY = 'training-planning-alice-push';

    public function __construct(
        private readonly TrainingPlanningEntryDataModelFactory $entryFactory,
        private readonly TrainingPlanningEntryPersisterGateway $entryPersisterGateway,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $entry = $this->entryFactory->build(
            $this->getReference(UserFixtures::ALICE, UserDataModel::class),
            $this->getReference(RoutineFixtures::ALICE_PUSH, RoutineDataModel::class),
            new PlanningScheduleDataInput(
                rule: PlanningRuleRegistry::WEEKLY,
                startDate: '2026-11-02',
                weekdays: [2, 4],
                startTime: '18:00',
                durationInMinutes: 75,
            ),
        );

        $this->entryPersisterGateway->create($entry);
        $this->addReference(self::ALICE_PUSH_TUESDAY_THURSDAY, $entry);
    }

    /** @return list<class-string> */
    public function getDependencies(): array
    {
        return [UserFixtures::class, RoutineFixtures::class];
    }
}
