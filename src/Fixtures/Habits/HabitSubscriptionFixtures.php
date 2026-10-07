<?php

declare(strict_types=1);

namespace App\Fixtures\Habits;

use App\Domain\DTO\DataModel\Habits\HabitDataModel;
use App\Domain\DTO\DataModel\Habits\HabitSubscriptionDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Gateway\Persister\Habits\HabitSubscriptionPersisterGateway;
use App\Fixtures\User\UserFixtures;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Alice keeps one manual habit and one fed by the step tracker — enough to exercise both a tick by
 * hand and a keep by tracker. Bob keeps none.
 */
final class HabitSubscriptionFixtures extends Fixture implements DependentFixtureInterface
{
    public const string ALICE_READING = 'habit-subscription-alice-reading';
    public const string ALICE_WALK = 'habit-subscription-alice-walk';

    public function __construct(private readonly HabitSubscriptionPersisterGateway $habitSubscriptionPersisterGateway)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $alice = $this->getReference(UserFixtures::ALICE, UserDataModel::class);

        $this->addReference(
            self::ALICE_READING,
            $this->subscribe($alice, $this->getReference(HabitFixtures::READING, HabitDataModel::class)),
        );
        $this->addReference(
            self::ALICE_WALK,
            $this->subscribe($alice, $this->getReference(HabitFixtures::WALK, HabitDataModel::class)),
        );
    }

    private function subscribe(UserDataModel $owner, HabitDataModel $habit): HabitSubscriptionDataModel
    {
        $subscription = new HabitSubscriptionDataModel();
        $subscription->owner = $owner;
        $subscription->habit = $habit;
        $subscription->isActive = true;

        return $this->habitSubscriptionPersisterGateway->create($subscription);
    }

    /** @return list<class-string> */
    public function getDependencies(): array
    {
        return [HabitFixtures::class, UserFixtures::class];
    }
}
