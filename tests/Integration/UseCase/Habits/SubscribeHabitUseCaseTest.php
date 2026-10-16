<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Habits;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Fixtures\Habits\HabitFixtures;
use App\Fixtures\Habits\HabitSubscriptionFixtures;
use App\Fixtures\User\UserFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Habits\CompleteHabitUseCase;
use App\UseCase\Habits\ListHabitsUseCase;
use App\UseCase\Habits\SubscribeHabitUseCase;
use App\UseCase\Habits\UnsubscribeHabitUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Taking up a habit of the catalogue, or resuming one dropped before. */
final class SubscribeHabitUseCaseTest extends KernelTestCase
{
    use HabitTestTrait;
    use LoadFixturesTrait;
    use QueryCountTrait;
    use SignedInUserTrait;

    private ListHabitsUseCase $list;
    private CompleteHabitUseCase $complete;
    private SubscribeHabitUseCase $subscribe;
    private UnsubscribeHabitUseCase $unsubscribe;
    private UserDataModel $alice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->list = self::getContainer()->get(ListHabitsUseCase::class);
        $this->complete = self::getContainer()->get(CompleteHabitUseCase::class);
        $this->subscribe = self::getContainer()->get(SubscribeHabitUseCase::class);
        $this->unsubscribe = self::getContainer()->get(UnsubscribeHabitUseCase::class);

        $this->loadFixtures(HabitSubscriptionFixtures::class);

        $this->alice = $this->getReference(UserFixtures::ALICE, UserDataModel::class);
    }

    /** Dropping a habit keeps its days: resuming it picks the run back up rather than starting over. */
    public function testSubscribingKeepsHistoryAcrossADrop(): void
    {
        $meditate = $this->habitId(HabitFixtures::MEDITATE);

        $this->subscribe->execute($this->currentUser($this->idOf($this->alice)), $meditate);
        $this->complete->execute($this->currentUser($this->idOf($this->alice)), $meditate);
        $this->unsubscribe->execute($this->currentUser($this->idOf($this->alice)), $meditate);

        // Dropped: it no longer shows.
        self::assertCount(2, $this->list->execute($this->currentUser($this->idOf($this->alice))));

        // Resumed: it is back, and today is still kept.
        $this->subscribe->execute($this->currentUser($this->idOf($this->alice)), $meditate);
        self::assertTrue($this->habitOf($this->alice, HabitFixtures::MEDITATE)->isCompletedToday);
    }

    public function testSubscribingToAHabitStaysWithinItsQueryBudget(): void
    {
        $aliceId = $this->idOf($this->alice);
        $meditate = $this->habitId(HabitFixtures::MEDITATE);

        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($aliceId),
            fn (UserDataModel $currentUser) => $this->subscribe->execute($currentUser, $meditate),
        );
    }
}
