<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\EventHandler\Training;

use App\Domain\DTO\Event\EventInterface;
use App\Domain\DTO\Event\Training\WorkoutBlockDeletedEvent;
use App\Domain\DTO\Event\Training\WorkoutCreatedEvent;
use App\Domain\DTO\Event\Training\WorkoutExerciseDeletedEvent;
use App\Domain\DTO\Event\Training\WorkoutSetDeletedEvent;
use App\Domain\DTO\Event\Training\WorkoutSetUpdatedEvent;
use App\Domain\DTO\Input\Training\AddWorkoutBlockDataInput;
use App\Domain\DTO\Input\Training\AddWorkoutBlockExerciseDataInput;
use App\Domain\DTO\Input\Training\AddWorkoutSetDataInput;
use App\Domain\DTO\Input\Training\StartWorkoutDataInput;
use App\Domain\DTO\Input\Training\UpdateWorkoutSetDataInput;
use App\Domain\DTO\Output\Training\WorkoutDataOutput;
use App\Fixtures\Training\MovementFixtures;
use App\Fixtures\Training\WorkoutFixtures;
use App\Infrastructure\EventHandler\Training\RefreshWorkoutSummaryEventHandler;
use App\Tests\Integration\Infrastructure\EventHandler\EventHandlerTestTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\UseCase\Training\WorkoutTestTrait;
use App\UseCase\Training\AddWorkoutBlockUseCase;
use App\UseCase\Training\AddWorkoutSetUseCase;
use App\UseCase\Training\DeleteWorkoutBlockUseCase;
use App\UseCase\Training\DeleteWorkoutExerciseUseCase;
use App\UseCase\Training\DeleteWorkoutSetUseCase;
use App\UseCase\Training\StartWorkoutUseCase;
use App\UseCase\Training\UncompleteWorkoutSetUseCase;
use App\UseCase\Training\UpdateWorkoutSetUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** A workout's summary: created with it, and added up again each time one of its sets moves. */
final class RefreshWorkoutSummaryEventHandlerTest extends KernelTestCase
{
    use EventHandlerTestTrait;
    use QueryCountTrait;
    use WorkoutTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadWorkoutFixtures();
    }

    public function testAWorkoutStartedHasAnEmptySummary(): void
    {
        $event = $this->eventRaisedBy(WorkoutCreatedEvent::class, fn () => $this->start());

        $this->handle($event);

        $summary = $this->summaryOf($event->workout->id ?? 0);
        self::assertSame(0, $summary->setCount);
        self::assertNull($summary->volumeInKilograms);
        self::assertSame(0, $summary->personalBestCount);
    }

    /** Laid out like a past workout, a copy has done nothing yet: its sets are all still to do. */
    public function testACopyStartsWithAnEmptySummary(): void
    {
        $event = $this->eventRaisedBy(WorkoutCreatedEvent::class, fn () => $this->copy(WorkoutFixtures::ALICE_FINISHED));

        $this->handle($event);

        self::assertSame(0, $this->summaryOf($event->workout->id ?? 0)->setCount);
    }

    public function testASetCountsOnceTickedAndNoLongerOnceUnticked(): void
    {
        $workout = $this->withHandlers(fn () => $this->addSet($this->benchWorkout(), new AddWorkoutSetDataInput(reps: 8, weightInKilograms: 50.0)));
        self::assertSame(0, $this->summaryOf($workout->id)->setCount);

        $this->handle($this->eventRaisedBy(WorkoutSetUpdatedEvent::class, fn () => $this->complete($workout, $this->lastSet($workout))));
        $summary = $this->summaryOf($workout->id);
        self::assertSame(1, $summary->setCount);
        self::assertSame(400.0, (float) $summary->volumeInKilograms);

        $this->handle($this->eventRaisedBy(WorkoutSetUpdatedEvent::class, fn () => $this->useCase(UncompleteWorkoutSetUseCase::class)->execute(
            $this->currentUser($this->aliceId), $workout->id, $this->lastSet($workout)->id,
        )));
        $summary = $this->summaryOf($workout->id);
        self::assertSame(0, $summary->setCount);
        self::assertNull($summary->volumeInKilograms);
    }

    public function testCorrectingADoneSetMovesTheTotals(): void
    {
        $workout = $this->withHandlers(fn () => $this->addDoneSet($this->benchWorkout(), new AddWorkoutSetDataInput(reps: 8, weightInKilograms: 50.0)));

        $this->handle($this->eventRaisedBy(WorkoutSetUpdatedEvent::class, fn () => $this->useCase(UpdateWorkoutSetUseCase::class)->execute(
            $this->currentUser($this->aliceId), $workout->id, $this->lastSet($workout)->id, new UpdateWorkoutSetDataInput(reps: 10, weightInKilograms: 50.0),
        )));

        self::assertSame(500.0, (float) $this->summaryOf($workout->id)->volumeInKilograms);
    }

    public function testRemovingASetTakesItOut(): void
    {
        $workout = $this->withHandlers(function (): WorkoutDataOutput {
            $workout = $this->addDoneSet($this->benchWorkout(), new AddWorkoutSetDataInput(reps: 8, weightInKilograms: 50.0));

            return $this->addDoneSet($workout, new AddWorkoutSetDataInput(reps: 6, weightInKilograms: 50.0));
        });

        $this->handle($this->eventRaisedBy(WorkoutSetDeletedEvent::class, fn () => $this->useCase(DeleteWorkoutSetUseCase::class)->execute(
            $this->currentUser($this->aliceId), $workout->id, $this->lastSet($workout)->id,
        )));

        $summary = $this->summaryOf($workout->id);
        self::assertSame(1, $summary->setCount);
        self::assertSame(400.0, (float) $summary->volumeInKilograms);
    }

    /** A movement removed from a superset takes its sets with it; a block removed, all of its own. */
    public function testRemovingAMovementOrABlockTakesTheirSetsOut(): void
    {
        $workout = $this->withHandlers(fn () => $this->benchAndPushUpSupersetDone());
        self::assertSame(2, $this->summaryOf($workout->id)->setCount);

        $this->handle($this->eventRaisedBy(WorkoutExerciseDeletedEvent::class, fn () => $this->useCase(DeleteWorkoutExerciseUseCase::class)->execute(
            $this->currentUser($this->aliceId), $workout->id, $workout->blocks[0]->exercises[1]->id,
        )));
        $summary = $this->summaryOf($workout->id);
        self::assertSame(1, $summary->setCount);
        self::assertSame(400.0, (float) $summary->volumeInKilograms);

        $this->handle($this->eventRaisedBy(WorkoutBlockDeletedEvent::class, fn () => $this->useCase(DeleteWorkoutBlockUseCase::class)->execute(
            $this->currentUser($this->aliceId), $workout->id, $workout->blocks[0]->id,
        )));
        $summary = $this->summaryOf($workout->id);
        self::assertSame(0, $summary->setCount);
        self::assertNull($summary->volumeInKilograms);
    }

    public function testRefreshingTheSummaryStaysWithinItsQueryBudget(): void
    {
        $workout = $this->inProgressPush();
        $setId = $workout->blocks[0]->exercises[0]->sets[1]->id;

        $this->assertQueryCountAtMost(
            fn () => $this->eventRaisedBy(WorkoutSetUpdatedEvent::class, fn () => $this->useCase(UpdateWorkoutSetUseCase::class)->execute(
                $this->currentUser($this->aliceId), $workout->id, $setId, new UpdateWorkoutSetDataInput(reps: 6, weightInKilograms: 72.5),
            )),
            fn (EventInterface $event) => $this->handle($event),
        );
    }

    private function handler(): RefreshWorkoutSummaryEventHandler
    {
        return self::getContainer()->get(RefreshWorkoutSummaryEventHandler::class);
    }

    /** A bench press and push-up superset, one set of each, both done: 40 kg × 10 and 15 push-ups. */
    private function benchAndPushUpSupersetDone(): WorkoutDataOutput
    {
        $workout = $this->useCase(StartWorkoutUseCase::class)->execute($this->currentUser($this->aliceId), new StartWorkoutDataInput());
        $workout = $this->useCase(AddWorkoutBlockUseCase::class)->execute($this->currentUser($this->aliceId), $workout->id, new AddWorkoutBlockDataInput([
            new AddWorkoutBlockExerciseDataInput($this->movementId(MovementFixtures::BENCH_PRESS_BARBELL)),
            new AddWorkoutBlockExerciseDataInput($this->movementId(MovementFixtures::PUSH_UP)),
        ]));
        [$bench, $pushUp] = $workout->blocks[0]->exercises;

        $workout = $this->useCase(AddWorkoutSetUseCase::class)->execute($this->currentUser($this->aliceId), $workout->id, $bench->id, new AddWorkoutSetDataInput(reps: 10, weightInKilograms: 40.0));
        $workout = $this->useCase(AddWorkoutSetUseCase::class)->execute($this->currentUser($this->aliceId), $workout->id, $pushUp->id, new AddWorkoutSetDataInput(reps: 15));
        foreach ($workout->blocks[0]->exercises as $exercise) {
            $workout = $this->complete($workout, $exercise->sets[0]);
        }

        return $workout;
    }
}
