<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\EventHandler\Planning;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Event\EventInterface;
use App\Domain\DTO\Event\Habits\HabitSubscriptionUpdatedEvent;
use App\Domain\Gateway\Provider\Planning\HabitPlanningEntryProviderGateway;
use App\Fixtures\Habits\HabitFixtures;
use App\Fixtures\Planning\HabitPlanningEntryFixtures;
use App\Fixtures\User\UserFixtures;
use App\Infrastructure\EventHandler\Planning\DeleteHabitPlanningEntriesEventHandler;
use App\Tests\Integration\Infrastructure\EventHandler\EventHandlerTestTrait;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\Tests\Integration\UseCase\Habits\HabitTestTrait;
use App\UseCase\Habits\SubscribeHabitUseCase;
use App\UseCase\Habits\UnsubscribeHabitUseCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Dropping a habit takes its planning entries with it: Alice reads on Mondays, Wednesdays and Fridays. */
final class DeleteHabitPlanningEntriesEventHandlerTest extends KernelTestCase
{
    use EventHandlerTestTrait;
    use HabitTestTrait;
    use LoadFixturesTrait;
    use QueryCountTrait;
    use SignedInUserTrait;

    private UserDataModel $alice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadFixtures(HabitPlanningEntryFixtures::class);
        $this->alice = $this->getReference(UserFixtures::ALICE, UserDataModel::class);
    }

    public function testDroppingTheHabitDeletesItsEntries(): void
    {
        $this->handle($this->eventRaisedBy(HabitSubscriptionUpdatedEvent::class, fn () => $this->unsubscribe()));

        self::getContainer()->get(EntityManagerInterface::class)->clear();
        self::assertSame([], self::getContainer()->get(HabitPlanningEntryProviderGateway::class)->findAllForOwner($this->currentUser($this->idOf($this->alice))));
    }

    /** Taking it back up writes the subscription too, and leaves the planning alone. */
    public function testTakingTheHabitBackUpDeletesNothing(): void
    {
        $this->withHandlers(fn () => $this->unsubscribe());
        $event = $this->eventRaisedBy(HabitSubscriptionUpdatedEvent::class, fn () => self::getContainer()->get(SubscribeHabitUseCase::class)->execute($this->currentUser($this->idOf($this->alice)), $this->habitId(HabitFixtures::READING)));

        self::assertFalse($event->deactivates);
        $this->assertQueryCountAtMost(fn () => $event, fn (EventInterface $event) => $this->handle($event), max: 0);
    }

    public function testDeletingTheEntriesStaysWithinItsQueryBudget(): void
    {
        $this->assertQueryCountAtMost(
            fn () => $this->eventRaisedBy(HabitSubscriptionUpdatedEvent::class, fn () => $this->unsubscribe()),
            fn (EventInterface $event) => $this->handle($event),
            // One DELETE, whatever the number of entries.
            max: 1,
        );
    }

    private function handler(): DeleteHabitPlanningEntriesEventHandler
    {
        return self::getContainer()->get(DeleteHabitPlanningEntriesEventHandler::class);
    }

    private function unsubscribe(): void
    {
        self::getContainer()->get(UnsubscribeHabitUseCase::class)->execute($this->currentUser($this->idOf($this->alice)), $this->habitId(HabitFixtures::READING));
    }
}
