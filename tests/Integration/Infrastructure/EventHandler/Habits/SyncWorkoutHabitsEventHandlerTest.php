<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\EventHandler\Habits;

use App\Domain\DTO\Event\EventInterface;
use App\Domain\DTO\Event\Training\WorkoutDeletedEvent;
use App\Domain\DTO\Event\Training\WorkoutUpdatedEvent;
use App\Domain\DTO\Input\Habits\CreateHabitDataInput;
use App\Domain\DTO\Input\Training\AddWorkoutSetDataInput;
use App\Domain\DTO\Input\Training\UpdateWorkoutDataInput;
use App\Domain\Registry\Habits\HabitIconRegistry;
use App\Domain\Registry\Habits\HabitSourceRegistry;
use App\Domain\Registry\Habits\HabitTrackerRegistry;
use App\Fixtures\Training\MovementFixtures;
use App\Infrastructure\EventHandler\Habits\SyncWorkoutHabitsEventHandler;
use App\Tests\Integration\Infrastructure\EventHandler\EventHandlerTestTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\UseCase\Training\WorkoutTestTrait;
use App\UseCase\Habits\Admin\CreateHabitUseCase;
use App\UseCase\Habits\ListHabitsUseCase;
use App\UseCase\Habits\SubscribeHabitUseCase;
use App\UseCase\Training\DeleteWorkoutUseCase;
use App\UseCase\Training\UpdateWorkoutUseCase;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** The workout habits follow how many workouts were finished on a day: Alice keeps « one a day ». */
final class SyncWorkoutHabitsEventHandlerTest extends KernelTestCase
{
    use EventHandlerTestTrait;
    use QueryCountTrait;
    use WorkoutTestTrait;

    private int $habitId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadWorkoutFixtures();

        $this->habitId = $this->useCase(CreateHabitUseCase::class)->execute($this->currentAdmin(), new CreateHabitDataInput(
            'Une séance', HabitIconRegistry::DUMBBELL, HabitSourceRegistry::TRACKER, HabitTrackerRegistry::WORKOUT, 1,
        ))->id;
        $this->useCase(SubscribeHabitUseCase::class)->execute($this->currentUser($this->aliceId), $this->habitId);
    }

    public function testFinishingKeepsTheHabitAndDeletingUnkeepsIt(): void
    {
        $workout = $this->withHandlers(fn () => $this->addDoneSet($this->addBlock($this->start(), MovementFixtures::PUSH_UP), new AddWorkoutSetDataInput(reps: 10)));
        self::assertFalse($this->isKeptToday());

        $this->handle($this->eventRaisedBy(WorkoutUpdatedEvent::class, fn () => $this->finish($workout)));
        self::assertTrue($this->isKeptToday());

        $this->handle($this->eventRaisedBy(WorkoutDeletedEvent::class, fn () => $this->useCase(DeleteWorkoutUseCase::class)->execute($this->currentUser($this->aliceId), $workout->id)));
        self::assertFalse($this->isKeptToday());
    }

    /** One in progress counts for nothing: naming it keeps nothing. */
    public function testAWorkoutInProgressKeepsNothing(): void
    {
        $workout = $this->withHandlers(fn () => $this->start());

        $this->handle($this->eventRaisedBy(WorkoutUpdatedEvent::class, fn () => $this->useCase(UpdateWorkoutUseCase::class)->execute(
            $this->currentUser($this->aliceId), $workout->id, new UpdateWorkoutDataInput('Jambes', null, null),
        )));

        self::assertFalse($this->isKeptToday());
    }

    /** A finished workout stays on its day: renaming it recounts nothing. */
    public function testRenamingAFinishedWorkoutCostsNothing(): void
    {
        $workout = $this->withHandlers(fn () => $this->finish($this->addDoneSet($this->addBlock($this->start(), MovementFixtures::PUSH_UP), new AddWorkoutSetDataInput(reps: 10))));

        $this->assertQueryCountAtMost(
            fn () => $this->eventRaisedBy(WorkoutUpdatedEvent::class, fn () => $this->useCase(UpdateWorkoutUseCase::class)->execute(
                $this->currentUser($this->aliceId), $workout->id, new UpdateWorkoutDataInput('Jambes'),
            )),
            fn (EventInterface $event) => $this->handle($event),
            max: 0,
        );
        self::assertTrue($this->isKeptToday());
    }

    public function testKeepingTheHabitsStaysWithinItsQueryBudget(): void
    {
        $workout = $this->withHandlers(fn () => $this->addDoneSet($this->addBlock($this->start(), MovementFixtures::PUSH_UP), new AddWorkoutSetDataInput(reps: 10)));

        $this->assertQueryCountAtMost(
            fn () => $this->eventRaisedBy(WorkoutUpdatedEvent::class, fn () => $this->finish($workout)),
            fn (EventInterface $event) => $this->handle($event),
        );
    }

    private function handler(): SyncWorkoutHabitsEventHandler
    {
        return self::getContainer()->get(SyncWorkoutHabitsEventHandler::class);
    }

    private function isKeptToday(): bool
    {
        foreach ($this->useCase(ListHabitsUseCase::class)->execute($this->currentUser($this->aliceId)) as $habit) {
            if ($this->habitId === $habit->habitId) {
                return $habit->isCompletedToday;
            }
        }

        throw new LogicException('The habit is not in the list.');
    }
}
