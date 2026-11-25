<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\EventHandler\Planning;

use App\Domain\DTO\DataModel\Planning\HabitPlanningEntryDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Event\EventInterface;
use App\Domain\DTO\Event\Habits\HabitSubscriptionCreatedEvent;
use App\Domain\DTO\Event\Habits\HabitSubscriptionUpdatedEvent;
use App\Domain\Factory\DataModelFactory\Planning\HabitPlanningEntryDataModelFactory;
use App\Domain\Gateway\Provider\Planning\HabitPlanningEntryProviderGateway;
use App\Domain\Registry\Planning\PlanningRuleRegistry;
use App\Domain\Tracking\DayClock;
use App\Fixtures\Habits\HabitFixtures;
use App\Fixtures\Habits\HabitSubscriptionFixtures;
use App\Fixtures\User\UserFixtures;
use App\Infrastructure\EventHandler\Planning\CreateDefaultHabitPlanningEntryEventHandler;
use App\Tests\Integration\Infrastructure\EventHandler\EventHandlerTestTrait;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\Tests\Integration\UseCase\Habits\HabitTestTrait;
use App\UseCase\Habits\SubscribeHabitUseCase;
use App\UseCase\Habits\UnsubscribeHabitUseCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** A habit taken on, or taken back up, is planned every day from today: Alice takes up meditating. */
final class CreateDefaultHabitPlanningEntryEventHandlerTest extends KernelTestCase
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

        $this->loadFixtures(HabitSubscriptionFixtures::class);
        $this->alice = $this->getReference(UserFixtures::ALICE, UserDataModel::class);
    }

    public function testTakingAHabitOnPlansItEveryDayFromToday(): void
    {
        $this->handle($this->eventRaisedBy(HabitSubscriptionCreatedEvent::class, fn () => $this->subscribe(HabitFixtures::MEDITATE)));

        $entries = $this->entriesOf(HabitFixtures::MEDITATE);
        self::assertCount(1, $entries);
        self::assertSame(PlanningRuleRegistry::WEEKLY, $entries[0]->rule);
        self::assertSame(HabitPlanningEntryDataModelFactory::EVERY_WEEKDAY, $entries[0]->weekdays);
        self::assertSame(1, $entries[0]->weekInterval);
        self::assertNull($entries[0]->endDate);
        self::assertNull($entries[0]->startTimeInMinutes);
        self::assertSame(self::getContainer()->get(DayClock::class)->today()->format('Y-m-d'), $entries[0]->startDate->format('Y-m-d'));
        self::assertSame($this->habitId(HabitFixtures::MEDITATE), $entries[0]->subscription->habit->id);
    }

    /** Dropping it took its entries: taken back up, it starts again from every day. */
    public function testTakingAHabitBackUpPlansItEveryDayAgain(): void
    {
        $this->withHandlers(fn () => $this->unsubscribe(HabitFixtures::READING));
        $event = $this->eventRaisedBy(HabitSubscriptionUpdatedEvent::class, fn () => $this->subscribe(HabitFixtures::READING));

        self::assertTrue($event->activates);
        $this->handle($event);

        $entries = $this->entriesOf(HabitFixtures::READING);
        self::assertCount(1, $entries);
        self::assertSame(HabitPlanningEntryDataModelFactory::EVERY_WEEKDAY, $entries[0]->weekdays);
    }

    /** Dropping it writes the subscription too, and plans nothing. */
    public function testDroppingAHabitPlansNothing(): void
    {
        $event = $this->eventRaisedBy(HabitSubscriptionUpdatedEvent::class, fn () => $this->unsubscribe(HabitFixtures::READING));

        self::assertFalse($event->activates);
        $this->assertQueryCountAtMost(fn () => $event, fn (EventInterface $event) => $this->handle($event), max: 0);
    }

    public function testPlanningTheHabitStaysWithinItsQueryBudget(): void
    {
        $this->assertQueryCountAtMost(
            fn () => $this->eventRaisedBy(HabitSubscriptionCreatedEvent::class, fn () => $this->subscribe(HabitFixtures::MEDITATE)),
            fn (EventInterface $event) => $this->handle($event),
            // The INSERT of the entry.
            max: 1,
        );
    }

    private function handler(): CreateDefaultHabitPlanningEntryEventHandler
    {
        return self::getContainer()->get(CreateDefaultHabitPlanningEntryEventHandler::class);
    }

    private function subscribe(string $habit): void
    {
        self::getContainer()->get(SubscribeHabitUseCase::class)->execute($this->currentUser($this->idOf($this->alice)), $this->habitId($habit));
    }

    private function unsubscribe(string $habit): void
    {
        self::getContainer()->get(UnsubscribeHabitUseCase::class)->execute($this->currentUser($this->idOf($this->alice)), $this->habitId($habit));
    }

    /**
     * Alice's entries of that habit: the others she keeps have their own.
     *
     * @return list<HabitPlanningEntryDataModel>
     */
    private function entriesOf(string $habit): array
    {
        self::getContainer()->get(EntityManagerInterface::class)->clear();
        $entries = self::getContainer()->get(HabitPlanningEntryProviderGateway::class)->findAllForOwner($this->currentUser($this->idOf($this->alice)));

        return array_values(array_filter($entries, fn (HabitPlanningEntryDataModel $entry): bool => $this->habitId($habit) === $entry->subscription->habit->id));
    }
}
