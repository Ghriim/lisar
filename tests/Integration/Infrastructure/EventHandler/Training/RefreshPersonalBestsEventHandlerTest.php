<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\EventHandler\Training;

use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\Event\EventInterface;
use App\Domain\DTO\Event\Training\WorkoutDeletedEvent;
use App\Domain\DTO\Event\Training\WorkoutSetCreatedEvent;
use App\Domain\DTO\Event\Training\WorkoutSetDeletedEvent;
use App\Domain\DTO\Event\Training\WorkoutSetUpdatedEvent;
use App\Domain\DTO\Event\Training\WorkoutUpdatedEvent;
use App\Domain\DTO\Input\Training\AddWorkoutSetDataInput;
use App\Domain\DTO\Input\Training\UpdateWorkoutSetDataInput;
use App\Domain\DTO\Output\Training\WorkoutDataOutput;
use App\Domain\Registry\Training\PersonalBestKindRegistry as Kind;
use App\Fixtures\Training\MovementFixtures;
use App\Fixtures\Training\SetTypeFixtures;
use App\Fixtures\Training\WorkoutFixtures;
use App\Infrastructure\EventHandler\Training\RefreshPersonalBestsEventHandler;
use App\Tests\Integration\Infrastructure\EventHandler\EventHandlerTestTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\UseCase\Training\WorkoutTestTrait;
use App\UseCase\Training\CompleteWorkoutSetUseCase;
use App\UseCase\Training\DeleteWorkoutSetUseCase;
use App\UseCase\Training\DeleteWorkoutUseCase;
use App\UseCase\Training\GetWorkoutUseCase;
use App\UseCase\Training\UncompleteWorkoutSetUseCase;
use App\UseCase\Training\UpdateWorkoutSetUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * The personal bests, rebuilt from the done sets each time one moves, and counted on the summary
 * of every workout that holds one. The seeded « Push » holds the bench's 60 kg.
 */
final class RefreshPersonalBestsEventHandlerTest extends KernelTestCase
{
    use EventHandlerTestTrait;
    use QueryCountTrait;
    use WorkoutTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadWorkoutFixtures();
    }

    /** The set's own records follow in memory: they are what the use case hands back. */
    public function testTickingASetBeatsTheRecordAtOnce(): void
    {
        $workout = $this->withHandlers(fn () => $this->addSet($this->benchWorkout(), new AddWorkoutSetDataInput(reps: 5, weightInKilograms: 70.0)));
        $event = $this->eventRaisedBy(WorkoutSetUpdatedEvent::class, fn () => $this->complete($workout, $this->lastSet($workout)));

        $this->handle($event);

        self::assertContains(Kind::MAX_WEIGHT, array_map(static fn ($record) => $record->kind, $event->set->personalBests->toArray()));
        $record = $this->record($this->board(), MovementFixtures::BENCH_PRESS_BARBELL, Kind::MAX_WEIGHT);
        self::assertSame(70.0, $record?->current->value);
        self::assertSame([60.0, 70.0], array_map(static fn ($row) => $row->value, $record?->progression ?? []));
    }

    /** A set logged but not ticked is still to do: it beats nothing yet. */
    public function testASetNotTickedBeatsNothing(): void
    {
        $workout = $this->withHandlers(fn () => $this->benchWorkout());
        $event = $this->eventRaisedBy(WorkoutSetCreatedEvent::class, fn () => $this->addSet($workout, new AddWorkoutSetDataInput(reps: 5, weightInKilograms: 70.0)));

        $this->handle($event);

        self::assertCount(0, $event->set->personalBests);
        self::assertSame(60.0, $this->current($this->board(), MovementFixtures::BENCH_PRESS_BARBELL, Kind::MAX_WEIGHT));
    }

    /** A warm-up is light on purpose: however heavy, it sets no record. */
    public function testASetOfATypeThatDoesNotCountBeatsNothing(): void
    {
        $workout = $this->withHandlers(fn () => $this->addSet($this->benchWorkout(), new AddWorkoutSetDataInput(
            reps: 5, weightInKilograms: 100.0, setTypeId: $this->setTypeId(SetTypeFixtures::WARM_UP),
        )));
        $event = $this->eventRaisedBy(WorkoutSetUpdatedEvent::class, fn () => $this->complete($workout, $this->lastSet($workout)));

        $this->handle($event);

        self::assertCount(0, $event->set->personalBests);
        self::assertSame(60.0, $this->current($this->board(), MovementFixtures::BENCH_PRESS_BARBELL, Kind::MAX_WEIGHT));
    }

    public function testTheRecordsASetBeatsAreCountedOnItsWorkout(): void
    {
        $workout = $this->withHandlers(fn () => $this->addSet($this->benchWorkout(), new AddWorkoutSetDataInput(reps: 5, weightInKilograms: 70.0)));
        $event = $this->eventRaisedBy(WorkoutSetUpdatedEvent::class, fn () => $this->complete($workout, $this->lastSet($workout)));

        $this->handle($event);
        $beaten = count($event->set->personalBests);
        self::assertGreaterThan(0, $beaten);
        self::assertSame($beaten, $this->summaryOf($workout->id)->personalBestCount);

        $this->handle($this->eventRaisedBy(WorkoutSetUpdatedEvent::class, fn () => $this->untick($workout)));
        self::assertSame(0, $this->summaryOf($workout->id)->personalBestCount);
    }

    public function testUntickingASetHandsTheRecordBack(): void
    {
        $workout = $this->withHandlers(fn () => $this->addDoneSet($this->benchWorkout(), new AddWorkoutSetDataInput(reps: 5, weightInKilograms: 70.0)));
        $event = $this->eventRaisedBy(WorkoutSetUpdatedEvent::class, fn () => $this->untick($workout));

        $this->handle($event);

        self::assertCount(0, $event->set->personalBests);
        self::assertSame(60.0, $this->current($this->board(), MovementFixtures::BENCH_PRESS_BARBELL, Kind::MAX_WEIGHT));
    }

    public function testCorrectingASetDownHandsTheRecordBack(): void
    {
        $workout = $this->withHandlers(fn () => $this->addDoneSet($this->benchWorkout(), new AddWorkoutSetDataInput(reps: 5, weightInKilograms: 70.0)));

        $this->handle($this->eventRaisedBy(WorkoutSetUpdatedEvent::class, fn () => $this->useCase(UpdateWorkoutSetUseCase::class)->execute(
            $this->currentUser($this->aliceId), $workout->id, $this->lastSet($workout)->id, new UpdateWorkoutSetDataInput(reps: 5, weightInKilograms: 50.0),
        )));

        self::assertSame(60.0, $this->current($this->board(), MovementFixtures::BENCH_PRESS_BARBELL, Kind::MAX_WEIGHT));
    }

    /**
     * A record taken back by an older workout leaves the newer one: its count moves although none
     * of its own sets did.
     */
    public function testARecordTakenBackByAnotherWorkoutLeavesTheCount(): void
    {
        $workout = $this->withHandlers(fn () => $this->addDoneSet($this->benchWorkout(), new AddWorkoutSetDataInput(reps: 8, weightInKilograms: 70.0)));
        $before = $this->summaryOf($workout->id)->personalBestCount;
        self::assertGreaterThan(0, $before);

        // The seeded workout's heaviest bench set, corrected up past the new one.
        $seededId = $this->getReference(WorkoutFixtures::ALICE_FINISHED, WorkoutDataModel::class)->id ?? 0;
        $seeded = $this->useCase(GetWorkoutUseCase::class)->execute($this->currentUser($this->aliceId), $seededId);
        $this->handle($this->eventRaisedBy(WorkoutSetUpdatedEvent::class, fn () => $this->useCase(UpdateWorkoutSetUseCase::class)->execute(
            $this->currentUser($this->aliceId), $seededId, $seeded->blocks[0]->exercises[0]->sets[1]->id, new UpdateWorkoutSetDataInput(reps: 8, weightInKilograms: 90.0),
        )));

        self::assertLessThan($before, $this->summaryOf($workout->id)->personalBestCount);
    }

    public function testRemovingASetHandsTheRecordBack(): void
    {
        $workout = $this->withHandlers(fn () => $this->addDoneSet($this->benchWorkout(), new AddWorkoutSetDataInput(reps: 5, weightInKilograms: 70.0)));

        $this->handle($this->eventRaisedBy(WorkoutSetDeletedEvent::class, fn () => $this->useCase(DeleteWorkoutSetUseCase::class)->execute(
            $this->currentUser($this->aliceId), $workout->id, $this->lastSet($workout)->id,
        )));

        self::assertSame(60.0, $this->current($this->board(), MovementFixtures::BENCH_PRESS_BARBELL, Kind::MAX_WEIGHT));
    }

    public function testAbandoningAWorkoutHandsItsRecordsBack(): void
    {
        $workout = $this->withHandlers(fn () => $this->addDoneSet($this->benchWorkout(), new AddWorkoutSetDataInput(reps: 5, weightInKilograms: 70.0)));

        $this->handle($this->eventRaisedBy(WorkoutDeletedEvent::class, fn () => $this->useCase(DeleteWorkoutUseCase::class)->execute($this->currentUser($this->aliceId), $workout->id)));

        $board = $this->board();
        self::assertSame(60.0, $this->current($board, MovementFixtures::BENCH_PRESS_BARBELL, Kind::MAX_WEIGHT));
        self::assertSame(7.0, $this->session($board, Kind::MAX_SESSION_SETS)?->current->value);
    }

    /** Finishing gives a workout its length, and the length can be a record. */
    public function testFinishingAWorkoutCanSetTheLongestOne(): void
    {
        $workout = $this->withHandlers(fn () => $this->addDoneSet($this->benchWorkout(), new AddWorkoutSetDataInput(reps: 5, weightInKilograms: 50.0)));

        $this->handle($this->eventRaisedBy(WorkoutUpdatedEvent::class, fn () => $this->finish($workout)));

        // Started and finished in the same second: no longer than the seeded hour.
        $longest = $this->session($this->board(), Kind::LONGEST_SESSION);
        self::assertSame(3600.0, $longest?->current->value);
        self::assertCount(1, $longest?->progression ?? []);
    }

    /** A set that counts neither before nor after a write moves no record: nothing is read. */
    public function testAWriteThatCannotMoveARecordCostsNothing(): void
    {
        $workout = $this->inProgressPush();
        $setId = $workout->blocks[0]->exercises[0]->sets[1]->id;

        $this->assertQueryCountAtMost(
            fn () => $this->eventRaisedBy(WorkoutSetUpdatedEvent::class, fn () => $this->useCase(UpdateWorkoutSetUseCase::class)->execute(
                $this->currentUser($this->aliceId), $workout->id, $setId, new UpdateWorkoutSetDataInput(reps: 6, weightInKilograms: 72.5),
            )),
            fn (EventInterface $event) => $this->handle($event),
            max: 0,
        );
    }

    /** A set removed takes its rows with it through the database: its workout is recounted all the same. */
    public function testRemovingASetThatHeldRecordsRecountsItsWorkout(): void
    {
        $workout = $this->withHandlers(fn () => $this->addDoneSet($this->benchWorkout(), new AddWorkoutSetDataInput(reps: 5, weightInKilograms: 70.0)));
        self::assertGreaterThan(0, $this->summaryOf($workout->id)->personalBestCount);

        $this->handle($this->eventRaisedBy(WorkoutSetDeletedEvent::class, fn () => $this->useCase(DeleteWorkoutSetUseCase::class)->execute(
            $this->currentUser($this->aliceId), $workout->id, $this->lastSet($workout)->id,
        )));

        self::assertSame(0, $this->summaryOf($workout->id)->personalBestCount);
    }

    /** Ticking a set that beats the bench's records: the case that writes. */
    public function testRefreshingTheRecordsStaysWithinItsQueryBudget(): void
    {
        $workout = $this->withHandlers(fn () => $this->addSet($this->benchWorkout(), new AddWorkoutSetDataInput(reps: 5, weightInKilograms: 70.0)));
        $setId = $this->lastSet($workout)->id;

        $this->assertQueryCountAtMost(
            fn () => $this->eventRaisedBy(WorkoutSetUpdatedEvent::class, fn () => $this->useCase(CompleteWorkoutSetUseCase::class)->execute(
                $this->currentUser($this->aliceId), $workout->id, $setId,
            )),
            fn (EventInterface $event) => $this->handle($event),
            // Three reads to rebuild — the rows standing, the sets that count, the workouts'
            // tallies — one INSERT per record the set beats, 5 here, then two reads and an UPDATE
            // to recount.
            max: 11,
        );
    }

    private function handler(): RefreshPersonalBestsEventHandler
    {
        return self::getContainer()->get(RefreshPersonalBestsEventHandler::class);
    }

    private function untick(WorkoutDataOutput $workout): void
    {
        $this->useCase(UncompleteWorkoutSetUseCase::class)->execute($this->currentUser($this->aliceId), $workout->id, $this->lastSet($workout)->id);
    }
}
