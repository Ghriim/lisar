<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training;

use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\AddWorkoutSetDataInput;
use App\Domain\DTO\Input\Training\ListWorkoutsDataInput;
use App\Fixtures\Training\MovementFixtures;
use App\Fixtures\Training\WorkoutFixtures;
use App\Tests\Integration\QueryCountTrait;
use App\UseCase\Training\FinishWorkoutUseCase;
use App\UseCase\Training\GetWorkoutStatsUseCase;
use App\UseCase\Training\ListWorkoutsUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** The history: the finished workouts, summed up, the latest started first. */
final class ListWorkoutsUseCaseTest extends KernelTestCase
{
    use QueryCountTrait;
    use WorkoutTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadWorkoutFixtures();
    }

    public function testTheHistoryListsFinishedWorkoutsOnly(): void
    {
        $this->start();

        $page = $this->useCase(ListWorkoutsUseCase::class)->execute($this->currentUser($this->idOf($this->alice)), new ListWorkoutsDataInput());

        self::assertSame(1, $page->total);
        $summary = $page->items[0];
        self::assertSame('Push', $summary->name);
        self::assertSame(['Bench press (barbell)', 'Push-up', 'Farmer walk (dumbbell)'], $summary->movementNames);
        self::assertSame(8, $summary->setCount);
        self::assertSame(1780.0, $summary->volumeInKilograms);
        self::assertNull($summary->durationInSeconds);
        self::assertSame(80, $summary->distanceInMetres);
    }

    public function testTheHistoryIsPaginated(): void
    {
        $workout = $this->addDoneSet($this->addBlock($this->start(), MovementFixtures::PUSH_UP), new AddWorkoutSetDataInput(reps: 10));
        $this->finish($workout);

        $page = $this->useCase(ListWorkoutsUseCase::class)->execute($this->currentUser($this->idOf($this->alice)), new ListWorkoutsDataInput(page: 1, perPage: 1));

        self::assertSame(2, $page->total);
        self::assertCount(1, $page->items);
        // The latest started first.
        self::assertSame($workout->id, $page->items[0]->id);
    }

    /** The history counts the same records the bilan lists; a workout that beat none counts 0. */
    public function testTheHistoryCountsTheRecordsEachWorkoutBeat(): void
    {
        $seeded = $this->getReference(WorkoutFixtures::ALICE_FINISHED, WorkoutDataModel::class);
        $stats = $this->useCase(GetWorkoutStatsUseCase::class)->execute($this->currentUser($this->aliceId), $seeded->id ?? 0);

        // One light set, finished at once: no set record, no session record.
        $workout = $this->addSet($this->benchWorkout(), new AddWorkoutSetDataInput(reps: 5, weightInKilograms: 20.0));
        $this->complete($workout, $this->lastSet($workout));
        $this->useCase(FinishWorkoutUseCase::class)->execute($this->currentUser($this->aliceId), $workout->id);

        $page = $this->useCase(ListWorkoutsUseCase::class)->execute($this->currentUser($this->aliceId), new ListWorkoutsDataInput());

        $counts = [];
        foreach ($page->items as $summary) {
            $counts[$summary->id] = $summary->personalBestCount;
        }
        self::assertGreaterThan(0, $counts[$seeded->id]);
        self::assertSame(count($stats->personalBests), $counts[$seeded->id]);
        self::assertSame(0, $counts[$workout->id]);
    }

    public function testListingTheWorkoutsDoesNotGrowWithThem(): void
    {
        [$fewer, $more] = $this->assertQueryCountDoesNotGrow(
            fn () => $this->currentUser($this->idOf($this->alice)),
            fn (UserDataModel $currentUser) => $this->useCase(ListWorkoutsUseCase::class)->execute($currentUser, new ListWorkoutsDataInput()),
            fn () => $this->finish($this->addDoneSet($this->addBlock($this->start(), MovementFixtures::PUSH_UP), new AddWorkoutSetDataInput(reps: 10))),
        );

        self::assertCount(count($fewer->items) + 1, $more->items);
    }

    public function testListingTheWorkoutsStaysWithinItsQueryBudget(): void
    {
        $this->finish($this->addDoneSet($this->addBlock($this->start(), MovementFixtures::PUSH_UP), new AddWorkoutSetDataInput(reps: 10)));

        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($this->idOf($this->alice)),
            fn (UserDataModel $currentUser) => $this->useCase(ListWorkoutsUseCase::class)->execute($currentUser, new ListWorkoutsDataInput()),
        );
    }
}
