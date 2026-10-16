<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\EventHandler\Habits;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Event\EventInterface;
use App\Domain\DTO\Event\Tracking\StepDayCreatedEvent;
use App\Domain\DTO\Event\Tracking\StepDayUpdatedEvent;
use App\Domain\DTO\Input\Tracking\Step\SaveStepDayDataInput;
use App\Domain\DTO\Output\Tracking\Step\StepDayDataOutput;
use App\Fixtures\Habits\HabitFixtures;
use App\Fixtures\Habits\HabitSubscriptionFixtures;
use App\Fixtures\Tracking\Step\StepDayFixtures;
use App\Fixtures\User\UserFixtures;
use App\Infrastructure\EventHandler\Habits\SyncStepHabitsEventHandler;
use App\Tests\Integration\Infrastructure\EventHandler\EventHandlerTestTrait;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\Tests\Integration\UseCase\Habits\HabitTestTrait;
use App\UseCase\Tracking\Step\SaveStepDayUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** The step habits follow the day's count: Alice keeps the 5000-step habit. */
final class SyncStepHabitsEventHandlerTest extends KernelTestCase
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

        $this->loadFixtures(StepDayFixtures::class, HabitSubscriptionFixtures::class);
        $this->alice = $this->getReference(UserFixtures::ALICE, UserDataModel::class);
    }

    public function testTheHabitIsKeptWhenTheCountCrossesTheMark(): void
    {
        $this->handle($this->eventRaisedBy(StepDayCreatedEvent::class, fn () => $this->save(5000)));

        self::assertTrue($this->habitOf($this->alice, HabitFixtures::WALK)->isCompletedToday);
    }

    public function testTheHabitIsUnkeptWhenTheCountIsCorrectedBelowTheMark(): void
    {
        // Over the 5000-step mark first, then corrected under it.
        $this->withHandlers(fn () => $this->save(6000));
        self::assertTrue($this->habitOf($this->alice, HabitFixtures::WALK)->isCompletedToday);

        $this->handle($this->eventRaisedBy(StepDayUpdatedEvent::class, fn () => $this->save(4000)));

        self::assertFalse($this->habitOf($this->alice, HabitFixtures::WALK)->isCompletedToday);
    }

    public function testKeepingTheHabitsStaysWithinItsQueryBudget(): void
    {
        $this->withHandlers(fn () => $this->save(4000));

        $this->assertQueryCountAtMost(
            fn () => $this->eventRaisedBy(StepDayUpdatedEvent::class, fn () => $this->save(8432)),
            fn (EventInterface $event) => $this->handle($event),
        );
    }

    private function handler(): SyncStepHabitsEventHandler
    {
        return self::getContainer()->get(SyncStepHabitsEventHandler::class);
    }

    private function save(int $countInSteps): StepDayDataOutput
    {
        return self::getContainer()->get(SaveStepDayUseCase::class)->execute($this->currentUser($this->idOf($this->alice)), new SaveStepDayDataInput($countInSteps));
    }
}
