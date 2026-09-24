<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Habit;

use App\Domain\DTO\DataModel\HabitDataModel;
use App\Domain\DTO\DataModel\HabitSubscriptionDataModel;
use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\DTO\Input\Step\SaveStepDayDataInput;
use App\Domain\DTO\Output\Habit\HabitDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\HabitOutputFactory;
use App\Domain\Gateway\Provider\HabitEntryProviderGateway;
use App\Domain\Tracking\DayClock;
use App\Fixtures\HabitFixtures;
use App\Fixtures\HabitSubscriptionFixtures;
use App\Fixtures\UserFixtures;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\Tests\Integration\LoadFixturesTrait;
use App\UseCase\Habit\CompleteHabitUseCase;
use App\UseCase\Habit\ListHabitCatalogUseCase;
use App\UseCase\Habit\ListHabitsUseCase;
use App\UseCase\Habit\SubscribeHabitUseCase;
use App\UseCase\Habit\UncompleteHabitUseCase;
use App\UseCase\Habit\UnsubscribeHabitUseCase;
use App\UseCase\Step\SaveStepDayUseCase;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Keeping habits: the panel's list, ticking a manual one, and a tracker keeping its own. */
final class HabitTest extends KernelTestCase
{
    use LoadFixturesTrait;

    private ListHabitsUseCase $list;
    private CompleteHabitUseCase $complete;
    private UncompleteHabitUseCase $uncomplete;
    private SubscribeHabitUseCase $subscribe;
    private UnsubscribeHabitUseCase $unsubscribe;
    private ListHabitCatalogUseCase $catalog;
    private SaveStepDayUseCase $saveSteps;
    private UserDataModel $alice;
    private UserDataModel $bob;

    protected function setUp(): void
    {
        parent::setUp();

        $this->list = self::getContainer()->get(ListHabitsUseCase::class);
        $this->complete = self::getContainer()->get(CompleteHabitUseCase::class);
        $this->uncomplete = self::getContainer()->get(UncompleteHabitUseCase::class);
        $this->subscribe = self::getContainer()->get(SubscribeHabitUseCase::class);
        $this->unsubscribe = self::getContainer()->get(UnsubscribeHabitUseCase::class);
        $this->catalog = self::getContainer()->get(ListHabitCatalogUseCase::class);
        $this->saveSteps = self::getContainer()->get(SaveStepDayUseCase::class);

        $this->loadFixtures(HabitSubscriptionFixtures::class);

        $this->alice = $this->getReference(UserFixtures::ALICE, UserDataModel::class);
        $this->bob = $this->getReference(UserFixtures::BOB_DEACTIVATED, UserDataModel::class);
    }

    public function testItListsTheHabitsOneKeepsEachWithAWeek(): void
    {
        $habits = $this->list->execute($this->idOf($this->alice));

        self::assertCount(2, $habits);
        // By habit name: "Lire" before "Marcher 10 000 pas".
        self::assertSame('Lire', $habits[0]->name);
        self::assertSame('Marcher 10 000 pas', $habits[1]->name);
        self::assertCount(HabitOutputFactory::WINDOW_IN_DAYS, $habits[0]->days);
        self::assertFalse($habits[0]->isCompletedToday);
    }

    public function testSomeoneWhoKeepsNoHabitHasAnEmptyList(): void
    {
        self::assertCount(0, $this->list->execute($this->idOf($this->bob)));
    }

    public function testTickingAManualHabitKeepsToday(): void
    {
        $output = $this->complete->execute($this->idOf($this->alice), $this->habitId(HabitFixtures::READING));

        self::assertTrue($output->isCompletedToday);
        self::assertTrue($output->days[HabitOutputFactory::WINDOW_IN_DAYS - 1]->isCompleted);
    }

    public function testTickingTwiceKeepsItOnce(): void
    {
        $this->complete->execute($this->idOf($this->alice), $this->habitId(HabitFixtures::READING));
        $this->complete->execute($this->idOf($this->alice), $this->habitId(HabitFixtures::READING));

        $subscription = $this->getReference(HabitSubscriptionFixtures::ALICE_READING, HabitSubscriptionDataModel::class);
        $since = self::getContainer()->get(DayClock::class)->today();

        self::assertCount(
            1,
            self::getContainer()->get(HabitEntryProviderGateway::class)->findForSubscriptionSince($subscription, $since),
        );
    }

    public function testUnTickingAManualHabitClearsToday(): void
    {
        $reading = $this->habitId(HabitFixtures::READING);

        $this->complete->execute($this->idOf($this->alice), $reading);
        $output = $this->uncomplete->execute($this->idOf($this->alice), $reading);

        self::assertFalse($output->isCompletedToday);
        self::assertFalse($output->days[HabitOutputFactory::WINDOW_IN_DAYS - 1]->isCompleted);
    }

    /** Un-ticking a day that was never kept changes nothing. */
    public function testUnTickingADayNeverKeptIsANoOp(): void
    {
        $output = $this->uncomplete->execute($this->idOf($this->alice), $this->habitId(HabitFixtures::READING));

        self::assertFalse($output->isCompletedToday);
    }

    public function testATrackerHabitIsNotUnTickedByHand(): void
    {
        $this->expectException(ValidationException::class);

        $this->uncomplete->execute($this->idOf($this->alice), $this->habitId(HabitFixtures::WALK));
    }

    public function testATrackerHabitIsNotTickedByHand(): void
    {
        $this->expectException(ValidationException::class);

        $this->complete->execute($this->idOf($this->alice), $this->habitId(HabitFixtures::WALK));
    }

    public function testCompletingAHabitOneDoesNotKeepIsNotFound(): void
    {
        $this->expectException(DataModelNotFoundException::class);

        $this->complete->execute($this->idOf($this->bob), $this->habitId(HabitFixtures::READING));
    }

    public function testTheStepTrackerKeepsItsHabitWhenItCrossesTheMark(): void
    {
        $this->saveSteps->execute($this->idOf($this->alice), new SaveStepDayDataInput(10000));

        self::assertTrue($this->habitOf($this->alice, HabitFixtures::WALK)->isCompletedToday);
    }

    public function testTheStepTrackerUnkeepsWhenCorrectedBelowTheMark(): void
    {
        $this->saveSteps->execute($this->idOf($this->alice), new SaveStepDayDataInput(10000));
        $this->saveSteps->execute($this->idOf($this->alice), new SaveStepDayDataInput(5000));

        self::assertFalse($this->habitOf($this->alice, HabitFixtures::WALK)->isCompletedToday);
    }

    /** Dropping a habit keeps its days: resuming it picks the run back up rather than starting over. */
    public function testSubscribingKeepsHistoryAcrossADrop(): void
    {
        $meditate = $this->habitId(HabitFixtures::MEDITATE);

        $this->subscribe->execute($this->idOf($this->alice), $meditate);
        $this->complete->execute($this->idOf($this->alice), $meditate);
        $this->unsubscribe->execute($this->idOf($this->alice), $meditate);

        // Dropped: it no longer shows.
        self::assertCount(2, $this->list->execute($this->idOf($this->alice)));

        // Resumed: it is back, and today is still kept.
        $this->subscribe->execute($this->idOf($this->alice), $meditate);
        self::assertTrue($this->habitOf($this->alice, HabitFixtures::MEDITATE)->isCompletedToday);
    }

    public function testTheCatalogFlagsWhatIsKept(): void
    {
        $subscribed = [];
        foreach ($this->catalog->execute($this->idOf($this->alice)) as $item) {
            $subscribed[$item->name] = $item->isSubscribed;
        }

        self::assertTrue($subscribed['Lire']);
        self::assertTrue($subscribed['Marcher 10 000 pas']);
        self::assertFalse($subscribed['Méditer']);
        // The retired habit is not offered.
        self::assertArrayNotHasKey('Ancienne habitude', $subscribed);
    }

    private function habitOf(UserDataModel $owner, string $habitReference): HabitDataOutput
    {
        $wanted = $this->habitId($habitReference);
        foreach ($this->list->execute($this->idOf($owner)) as $habit) {
            if ($wanted === $habit->habitId) {
                return $habit;
            }
        }

        throw new LogicException('The habit is not in the list.');
    }

    private function habitId(string $reference): int
    {
        return $this->getReference($reference, HabitDataModel::class)->id ?? 0;
    }

    private function idOf(UserDataModel $user): int
    {
        if (null === $user->id) {
            throw new LogicException('The seeded account has no id.');
        }

        return $user->id;
    }
}
