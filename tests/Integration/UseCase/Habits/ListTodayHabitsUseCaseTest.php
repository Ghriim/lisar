<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Habits;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Factory\OutputFactory\Habits\HabitOutputFactory;
use App\Domain\Tracking\DayClock;
use App\Fixtures\Habits\HabitFixtures;
use App\Fixtures\Habits\HabitSubscriptionFixtures;
use App\Fixtures\Planning\HabitPlanningEntryFixtures;
use App\Fixtures\User\UserFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Habits\CompleteHabitUseCase;
use App\UseCase\Habits\ListTodayHabitsUseCase;
use App\UseCase\Habits\SubscribeHabitUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** The dashboard's list: the habits one keeps that today asks for, each with its week. */
final class ListTodayHabitsUseCaseTest extends KernelTestCase
{
    use HabitTestTrait;
    use LoadFixturesTrait;
    use QueryCountTrait;
    use SignedInUserTrait;

    private ListTodayHabitsUseCase $list;
    private CompleteHabitUseCase $complete;
    private SubscribeHabitUseCase $subscribe;
    private UserDataModel $alice;
    private UserDataModel $bob;

    protected function setUp(): void
    {
        parent::setUp();

        $this->list = self::getContainer()->get(ListTodayHabitsUseCase::class);
        $this->complete = self::getContainer()->get(CompleteHabitUseCase::class);
        $this->subscribe = self::getContainer()->get(SubscribeHabitUseCase::class);

        $this->loadFixtures(HabitSubscriptionFixtures::class);

        $this->alice = $this->getReference(UserFixtures::ALICE, UserDataModel::class);
        $this->bob = $this->getReference(UserFixtures::BOB_DEACTIVATED, UserDataModel::class);
    }

    public function testWithoutAnyPlanningEveryKeptHabitIsForToday(): void
    {
        $habits = $this->list->execute($this->currentUser($this->idOf($this->alice)));

        self::assertSame(['Lire', 'Marcher 5000 pas'], array_map(static fn ($habit) => $habit->name, $habits));
        self::assertCount(HabitOutputFactory::WINDOW_IN_DAYS, $habits[0]->days);
    }

    /** Reading is planned on Mondays, Wednesdays and Fridays, the walk every day from today: reading is in the list on those days only. */
    public function testAHabitItsPlanningSkipsTodayIsLeftOut(): void
    {
        $this->loadFixtures(HabitPlanningEntryFixtures::class);
        $alice = $this->getReference(UserFixtures::ALICE, UserDataModel::class);
        $weekday = (int) self::getContainer()->get(DayClock::class)->today()->format('N');

        $names = array_map(static fn ($habit) => $habit->name, $this->list->execute($this->currentUser($this->idOf($alice))));

        self::assertSame(in_array($weekday, [1, 3, 5], true) ? ['Lire', 'Marcher 5000 pas'] : ['Marcher 5000 pas'], $names);
    }

    public function testSomeoneWhoKeepsNoHabitHasAnEmptyList(): void
    {
        self::assertCount(0, $this->list->execute($this->currentUser($this->idOf($this->bob))));
    }

    public function testListingTodaysHabitsStaysWithinItsQueryBudget(): void
    {
        $aliceId = $this->idOf($this->alice);
        $this->complete->execute($this->currentUser($aliceId), $this->habitId(HabitFixtures::READING));

        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($aliceId),
            fn (UserDataModel $currentUser) => $this->list->execute($currentUser),
        );
    }

    public function testListingTodaysHabitsDoesNotGrowWithThem(): void
    {
        $aliceId = $this->idOf($this->alice);

        [$fewer, $more] = $this->assertQueryCountDoesNotGrow(
            fn () => $this->currentUser($aliceId),
            fn (UserDataModel $currentUser) => $this->list->execute($currentUser),
            function () use ($aliceId): void {
                $this->subscribe->execute($this->currentUser($aliceId), $this->habitId(HabitFixtures::MEDITATE));
                $this->complete->execute($this->currentUser($aliceId), $this->habitId(HabitFixtures::MEDITATE));
                $this->subscribe->execute($this->currentUser($aliceId), $this->habitId(HabitFixtures::DRINK));
            },
        );

        self::assertCount(count($fewer) + 2, $more);
    }
}
