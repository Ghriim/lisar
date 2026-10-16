<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Tracking\Hydration;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Event\Tracking\HydrationEntryCreatedEvent;
use App\Domain\DTO\Input\Tracking\Hydration\CreateHydrationEntryDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\Tracking\Hydration\HydrationDayProviderGateway;
use App\Domain\Tracking\DayClock;
use App\Fixtures\User\UserFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\RecordedEventsTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Tracking\Hydration\CreateHydrationEntryUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Logging a drink on the day in progress — the day is written the first time — and answering the
 * whole day. The habits watching hydration follow its total, on its handler.
 */
final class CreateHydrationEntryUseCaseTest extends KernelTestCase
{
    use HydrationTestTrait;
    use LoadFixturesTrait;
    use QueryCountTrait;
    use SignedInUserTrait;
    use RecordedEventsTrait;

    private CreateHydrationEntryUseCase $create;
    private UserDataModel $alice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->create = self::getContainer()->get(CreateHydrationEntryUseCase::class);

        $this->loadFixtures(UserFixtures::class);

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

    /** The day the event carries holds every drink, the new one with the others: its handler reads the total off it. */
    public function testLoggingADrinkRaisesItsEvent(): void
    {
        $this->drink(1000);
        $this->forgetRaisedEvents();

        $this->drink(600);

        $entry = $this->assertEventRaised(HydrationEntryCreatedEvent::class)->entry;
        self::assertSame(600, $entry->volumeInMillilitres);
        self::assertSame(1600, $entry->hydrationDay->getTotalInMillilitres());
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
