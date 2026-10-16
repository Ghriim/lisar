<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\EventHandler\Habits;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Event\EventInterface;
use App\Domain\DTO\Event\Tracking\HydrationEntryCreatedEvent;
use App\Domain\DTO\Event\Tracking\HydrationEntryDeletedEvent;
use App\Domain\DTO\Event\Tracking\HydrationEntryUpdatedEvent;
use App\Domain\DTO\Input\Tracking\Hydration\UpdateHydrationEntryDataInput;
use App\Fixtures\Habits\HabitFixtures;
use App\Fixtures\Habits\HabitSubscriptionFixtures;
use App\Fixtures\User\UserFixtures;
use App\Infrastructure\EventHandler\Habits\SyncHydrationHabitsEventHandler;
use App\Tests\Integration\Infrastructure\EventHandler\EventHandlerTestTrait;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\Tests\Integration\UseCase\Habits\HabitTestTrait;
use App\Tests\Integration\UseCase\Tracking\Hydration\HydrationTestTrait;
use App\UseCase\Habits\SubscribeHabitUseCase;
use App\UseCase\Tracking\Hydration\DeleteHydrationEntryUseCase;
use App\UseCase\Tracking\Hydration\UpdateHydrationEntryUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** The habits watching hydration follow the day's total: Alice's « drink » habit asks 1500 ml. */
final class SyncHydrationHabitsEventHandlerTest extends KernelTestCase
{
    use EventHandlerTestTrait;
    use HabitTestTrait;
    use HydrationTestTrait;
    use LoadFixturesTrait;
    use QueryCountTrait;
    use SignedInUserTrait;

    private UserDataModel $alice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadFixtures(UserFixtures::class, HabitSubscriptionFixtures::class);
        $this->alice = $this->getReference(UserFixtures::ALICE, UserDataModel::class);

        self::getContainer()->get(SubscribeHabitUseCase::class)->execute($this->currentUser($this->idOf($this->alice)), $this->habitId(HabitFixtures::DRINK));
    }

    /** The day's total counts every drink, the one just logged with the others: 1000 + 600 crosses 1500. */
    public function testTheHabitIsKeptWhenTheDayCrossesTheMark(): void
    {
        $this->handle($this->eventRaisedBy(HydrationEntryCreatedEvent::class, fn () => $this->drink(1000)));
        self::assertFalse($this->habitOf($this->alice, HabitFixtures::DRINK)->isCompletedToday);

        $this->handle($this->eventRaisedBy(HydrationEntryCreatedEvent::class, fn () => $this->drink(600)));
        self::assertTrue($this->habitOf($this->alice, HabitFixtures::DRINK)->isCompletedToday);
    }

    public function testTheHabitIsUnkeptWhenADrinkIsCorrectedBelowTheMark(): void
    {
        $day = $this->withHandlers(function () {
            $this->drink(1000);

            return $this->drink(600);
        });
        self::assertTrue($this->habitOf($this->alice, HabitFixtures::DRINK)->isCompletedToday);

        $this->handle($this->eventRaisedBy(HydrationEntryUpdatedEvent::class, fn () => self::getContainer()->get(UpdateHydrationEntryUseCase::class)->execute(
            $this->currentUser($this->idOf($this->alice)), $this->entryId($day, 600), new UpdateHydrationEntryDataInput(300),
        )));

        self::assertFalse($this->habitOf($this->alice, HabitFixtures::DRINK)->isCompletedToday);
    }

    public function testTheHabitIsUnkeptWhenADrinkIsRemovedBelowTheMark(): void
    {
        $day = $this->withHandlers(function () {
            $this->drink(1000);

            return $this->drink(600);
        });

        $this->handle($this->eventRaisedBy(HydrationEntryDeletedEvent::class, fn () => self::getContainer()->get(DeleteHydrationEntryUseCase::class)->execute(
            $this->currentUser($this->idOf($this->alice)), $this->entryId($day, 600),
        )));

        self::assertFalse($this->habitOf($this->alice, HabitFixtures::DRINK)->isCompletedToday);
    }

    public function testKeepingTheHabitsStaysWithinItsQueryBudget(): void
    {
        $this->withHandlers(fn () => $this->drink(1000));

        $this->assertQueryCountAtMost(
            fn () => $this->eventRaisedBy(HydrationEntryCreatedEvent::class, fn () => $this->drink(600)),
            fn (EventInterface $event) => $this->handle($event),
        );
    }

    private function handler(): SyncHydrationHabitsEventHandler
    {
        return self::getContainer()->get(SyncHydrationHabitsEventHandler::class);
    }
}
