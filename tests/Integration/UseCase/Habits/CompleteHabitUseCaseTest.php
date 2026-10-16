<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Habits;

use App\Domain\DTO\DataModel\Habits\HabitSubscriptionDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\Habits\HabitOutputFactory;
use App\Domain\Gateway\Provider\Habits\HabitEntryProviderGateway;
use App\Domain\Tracking\DayClock;
use App\Fixtures\Habits\HabitFixtures;
use App\Fixtures\Habits\HabitSubscriptionFixtures;
use App\Fixtures\User\UserFixtures;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Habits\CompleteHabitUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Ticking a manual habit for today. A tracker habit is kept by its tracker, never by hand. */
final class CompleteHabitUseCaseTest extends KernelTestCase
{
    use HabitTestTrait;
    use LoadFixturesTrait;
    use QueryCountTrait;
    use SignedInUserTrait;

    private CompleteHabitUseCase $complete;
    private UserDataModel $alice;
    private UserDataModel $bob;

    protected function setUp(): void
    {
        parent::setUp();

        $this->complete = self::getContainer()->get(CompleteHabitUseCase::class);

        $this->loadFixtures(HabitSubscriptionFixtures::class);

        $this->alice = $this->getReference(UserFixtures::ALICE, UserDataModel::class);
        $this->bob = $this->getReference(UserFixtures::BOB_DEACTIVATED, UserDataModel::class);
    }

    public function testTickingAManualHabitKeepsToday(): void
    {
        $output = $this->complete->execute($this->currentUser($this->idOf($this->alice)), $this->habitId(HabitFixtures::READING));

        self::assertTrue($output->isCompletedToday);
        self::assertTrue($output->days[HabitOutputFactory::WINDOW_IN_DAYS - 1]->isCompleted);
    }

    public function testTickingTwiceKeepsItOnce(): void
    {
        $this->complete->execute($this->currentUser($this->idOf($this->alice)), $this->habitId(HabitFixtures::READING));
        $this->complete->execute($this->currentUser($this->idOf($this->alice)), $this->habitId(HabitFixtures::READING));

        $subscription = $this->getReference(HabitSubscriptionFixtures::ALICE_READING, HabitSubscriptionDataModel::class);
        $since = self::getContainer()->get(DayClock::class)->today();

        self::assertCount(
            1,
            self::getContainer()->get(HabitEntryProviderGateway::class)->findForSubscriptionSince($subscription, $since),
        );
    }

    public function testATrackerHabitIsNotTickedByHand(): void
    {
        $this->expectException(ValidationException::class);

        $this->complete->execute($this->currentUser($this->idOf($this->alice)), $this->habitId(HabitFixtures::WALK));
    }

    public function testCompletingAHabitOneDoesNotKeepIsNotFound(): void
    {
        $this->expectException(DataModelNotFoundException::class);

        $this->complete->execute($this->currentUser($this->idOf($this->bob)), $this->habitId(HabitFixtures::READING));
    }

    public function testCompletingAHabitStaysWithinItsQueryBudget(): void
    {
        $aliceId = $this->idOf($this->alice);
        $reading = $this->habitId(HabitFixtures::READING);

        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($aliceId),
            fn (UserDataModel $currentUser) => $this->complete->execute($currentUser, $reading),
        );
    }
}
