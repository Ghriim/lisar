<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Habits;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Factory\OutputFactory\Habits\HabitOutputFactory;
use App\Fixtures\Habits\HabitFixtures;
use App\Fixtures\Habits\HabitSubscriptionFixtures;
use App\Fixtures\User\UserFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Habits\CompleteHabitUseCase;
use App\UseCase\Habits\ListHabitsUseCase;
use App\UseCase\Habits\SubscribeHabitUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** The panel's list: the habits one keeps, each with its week. */
final class ListHabitsUseCaseTest extends KernelTestCase
{
    use HabitTestTrait;
    use LoadFixturesTrait;
    use QueryCountTrait;
    use SignedInUserTrait;

    private ListHabitsUseCase $list;
    private CompleteHabitUseCase $complete;
    private SubscribeHabitUseCase $subscribe;
    private UserDataModel $alice;
    private UserDataModel $bob;

    protected function setUp(): void
    {
        parent::setUp();

        $this->list = self::getContainer()->get(ListHabitsUseCase::class);
        $this->complete = self::getContainer()->get(CompleteHabitUseCase::class);
        $this->subscribe = self::getContainer()->get(SubscribeHabitUseCase::class);

        $this->loadFixtures(HabitSubscriptionFixtures::class);

        $this->alice = $this->getReference(UserFixtures::ALICE, UserDataModel::class);
        $this->bob = $this->getReference(UserFixtures::BOB_DEACTIVATED, UserDataModel::class);
    }

    public function testItListsTheHabitsOneKeepsEachWithAWeek(): void
    {
        $habits = $this->list->execute($this->currentUser($this->idOf($this->alice)));

        self::assertCount(2, $habits);
        // By habit name: "Lire" before "Marcher 5000 pas".
        self::assertSame('Lire', $habits[0]->name);
        self::assertSame('Marcher 5000 pas', $habits[1]->name);
        self::assertCount(HabitOutputFactory::WINDOW_IN_DAYS, $habits[0]->days);
        self::assertFalse($habits[0]->isCompletedToday);
    }

    public function testSomeoneWhoKeepsNoHabitHasAnEmptyList(): void
    {
        self::assertCount(0, $this->list->execute($this->currentUser($this->idOf($this->bob))));
    }

    public function testListingTheHabitsStaysWithinItsQueryBudget(): void
    {
        $aliceId = $this->idOf($this->alice);
        $this->complete->execute($this->currentUser($aliceId), $this->habitId(HabitFixtures::READING));

        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($aliceId),
            fn (UserDataModel $currentUser) => $this->list->execute($currentUser),
        );
    }

    public function testListingTheHabitsDoesNotGrowWithThem(): void
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
