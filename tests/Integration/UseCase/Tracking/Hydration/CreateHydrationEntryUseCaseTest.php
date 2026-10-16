<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Tracking\Hydration;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Tracking\Hydration\CreateHydrationEntryDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\Tracking\Hydration\HydrationDayProviderGateway;
use App\Domain\Tracking\DayClock;
use App\Fixtures\Habits\HabitFixtures;
use App\Fixtures\Habits\HabitSubscriptionFixtures;
use App\Fixtures\User\UserFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\Tests\Integration\UseCase\Habits\HabitTestTrait;
use App\UseCase\Habits\SubscribeHabitUseCase;
use App\UseCase\Tracking\Hydration\CreateHydrationEntryUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Logging a drink on the day in progress — the day is written the first time — and answering the
 * whole day. The habits watching hydration follow its total.
 */
final class CreateHydrationEntryUseCaseTest extends KernelTestCase
{
    use HabitTestTrait;
    use HydrationTestTrait;
    use LoadFixturesTrait;
    use QueryCountTrait;
    use SignedInUserTrait;

    private CreateHydrationEntryUseCase $create;
    private SubscribeHabitUseCase $subscribe;
    private UserDataModel $alice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->create = self::getContainer()->get(CreateHydrationEntryUseCase::class);
        $this->subscribe = self::getContainer()->get(SubscribeHabitUseCase::class);

        // The habit subscriptions are for the tracker-habit test: Alice keeps no hydration habit
        // until a test subscribes her.
        $this->loadFixtures(UserFixtures::class, HabitSubscriptionFixtures::class);

        $this->alice = $this->getReference(UserFixtures::ALICE, UserDataModel::class);
    }

    public function testItLogsAndTotals(): void
    {
        $this->create->execute($this->currentUser($this->aliceId()), new CreateHydrationEntryDataInput(250));
        $output = $this->create->execute($this->currentUser($this->aliceId()), new CreateHydrationEntryDataInput(500));

        self::assertSame(750, $output->totalInMillilitres);
        self::assertCount(2, $output->entries);

        // Re-read through the gateway: assert it was really persisted, on today's day.
        $clock = self::getContainer()->get(DayClock::class);
        $day = self::getContainer()->get(HydrationDayProviderGateway::class)
            ->findOneForOwnerAndDay($this->alice, $clock->today());
        self::assertNotNull($day);
        self::assertSame(750, $day->getTotalInMillilitres());
    }

    /**
     * The goal is written into the day the first time something is logged, so that raising it
     * later cannot turn a day that was reached into a day that was missed.
     */
    public function testTheDayKeepsTheGoalThatAppliedToIt(): void
    {
        $this->create->execute($this->currentUser($this->aliceId()), new CreateHydrationEntryDataInput(250));

        $clock = self::getContainer()->get(DayClock::class);
        $day = self::getContainer()->get(HydrationDayProviderGateway::class)
            ->findOneForOwnerAndDay($this->alice, $clock->today());

        self::assertNotNull($day);
        self::assertSame(1500, $day->goalInMillilitres);
    }

    public function testItRefusesAnImplausibleVolume(): void
    {
        try {
            $this->create->execute($this->currentUser($this->aliceId()), new CreateHydrationEntryDataInput(9000));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('volumeInMillilitres', $exception->violations);
        }
    }

    /** The day's total counts every drink, the one just logged with the others: 1000 + 600 crosses 1500. */
    public function testTheHydrationTrackerKeepsItsHabitWhenTheDayCrossesTheMark(): void
    {
        $this->subscribe->execute($this->currentUser($this->idOf($this->alice)), $this->habitId(HabitFixtures::DRINK));

        $this->drink(1000);
        self::assertFalse($this->habitOf($this->alice, HabitFixtures::DRINK)->isCompletedToday);

        $this->drink(600);
        self::assertTrue($this->habitOf($this->alice, HabitFixtures::DRINK)->isCompletedToday);
    }

    public function testLoggingADrinkStaysWithinItsQueryBudget(): void
    {
        $aliceId = $this->aliceId();
        $this->create->execute($this->currentUser($aliceId), new CreateHydrationEntryDataInput(250));

        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($aliceId),
            fn (UserDataModel $currentUser) => $this->create->execute($currentUser, new CreateHydrationEntryDataInput(500)),
        );
    }
}
