<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training;

use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Registry\Training\PersonalBestKindRegistry as Kind;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Fixtures\Training\WorkoutFixtures;
use App\Tests\Integration\QueryCountTrait;
use App\UseCase\Training\GetWorkoutStatsUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** A workout's bilan: its totals, the muscles it worked, the records it beat. */
final class GetWorkoutStatsUseCaseTest extends KernelTestCase
{
    use QueryCountTrait;
    use WorkoutTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadWorkoutFixtures();
    }

    /**
     * The seeded « Push »: four bench sets (one warm-up at 40 kg), then push-ups supersetted with
     * a farmer walk — a load carried over a distance, with no reps to multiply it by.
     */
    public function testItAddsAWorkoutUp(): void
    {
        $workout = $this->getReference(WorkoutFixtures::ALICE_FINISHED, WorkoutDataModel::class);

        $stats = $this->useCase(GetWorkoutStatsUseCase::class)->execute($this->currentUser($this->idOf($this->alice)), $workout->id ?? 0);

        self::assertSame(8, $stats->setCount);
        self::assertSame(1780.0, $stats->volumeInKilograms);
        self::assertNull($stats->durationInSeconds);
        self::assertSame(80, $stats->distanceInMetres);

        $shares = array_map(static fn ($muscle) => [$muscle->muscleName, $muscle->setShare, $muscle->percentage], $stats->muscles);
        self::assertSame([
            ['Mid chest', 6.0, 40.0],
            ['Front delts', 3.0, 20.0],
            ['Triceps', 3.0, 20.0],
            ['Full body', 2.0, 13.3],
            ['Abs', 1.0, 6.7],
        ], $shares);
    }

    public function testSomeoneElsesWorkoutStatsAreRefused(): void
    {
        $workout = $this->getReference(WorkoutFixtures::ALICE_FINISHED, WorkoutDataModel::class);

        try {
            $this->useCase(GetWorkoutStatsUseCase::class)->execute($this->currentUser($this->idOf($this->bob)), $workout->id ?? 0);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(['currentUser' => [IsOwnerConstraint::NOT_ALLOWED]], $exception->violations);
        }
    }

    /** The bilan lists what the workout beat. */
    public function testTheBilanListsTheRecordsTheWorkoutBeat(): void
    {
        $workout = $this->getReference(WorkoutFixtures::ALICE_FINISHED, WorkoutDataModel::class);

        $stats = $this->useCase(GetWorkoutStatsUseCase::class)->execute($this->currentUser($this->aliceId), $workout->id ?? 0);

        $kinds = array_map(static fn ($record) => $record->kind, $stats->personalBests);
        self::assertContains(Kind::MAX_WEIGHT, $kinds);
        self::assertContains(Kind::MAX_SESSION_SETS, $kinds);
        self::assertSame(Kind::MAX_WEIGHT, $stats->personalBests[0]->kind);
    }

    /** The fixtures go through the persisters: the seeded workout was summed up as it was logged. */
    public function testTheSeededWorkoutIsSummedUp(): void
    {
        $workoutId = $this->getReference(WorkoutFixtures::ALICE_FINISHED, WorkoutDataModel::class)->id ?? 0;
        $stats = $this->useCase(GetWorkoutStatsUseCase::class)->execute($this->currentUser($this->aliceId), $workoutId);

        $summary = $this->summaryOf($workoutId);

        self::assertSame(8, $summary->setCount);
        self::assertSame(1780.0, (float) $summary->volumeInKilograms);
        self::assertNull($summary->durationInSeconds);
        self::assertSame(80, $summary->distanceInMetres);
        self::assertSame(count($stats->personalBests), $summary->personalBestCount);
        self::assertGreaterThan(0, $summary->personalBestCount);
    }

    public function testReadingAWorkoutsStatsStaysWithinItsQueryBudget(): void
    {
        $workoutId = $this->getReference(WorkoutFixtures::ALICE_FINISHED, WorkoutDataModel::class)->id ?? 0;

        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($this->idOf($this->alice)),
            fn (UserDataModel $currentUser) => $this->useCase(GetWorkoutStatsUseCase::class)->execute($currentUser, $workoutId),
        );
    }
}
