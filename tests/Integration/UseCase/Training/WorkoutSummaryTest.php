<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training;

use App\Domain\DTO\DataModel\Training\MovementDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutSummaryDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\AddWorkoutBlockDataInput;
use App\Domain\DTO\Input\Training\AddWorkoutBlockExerciseDataInput;
use App\Domain\DTO\Input\Training\AddWorkoutSetDataInput;
use App\Domain\DTO\Input\Training\StartWorkoutDataInput;
use App\Domain\DTO\Input\Training\UpdateWorkoutSetDataInput;
use App\Domain\DTO\Output\Training\WorkoutDataOutput;
use App\Domain\DTO\Output\Training\WorkoutSetDataOutput;
use App\Domain\Gateway\Provider\Training\WorkoutSummaryProviderGateway;
use App\Fixtures\Training\MovementFixtures;
use App\Fixtures\Training\WorkoutFixtures;
use App\Fixtures\User\UserFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Training\AddWorkoutBlockUseCase;
use App\UseCase\Training\AddWorkoutSetUseCase;
use App\UseCase\Training\CompleteWorkoutSetUseCase;
use App\UseCase\Training\CopyWorkoutUseCase;
use App\UseCase\Training\DeleteWorkoutBlockUseCase;
use App\UseCase\Training\DeleteWorkoutExerciseUseCase;
use App\UseCase\Training\DeleteWorkoutSetUseCase;
use App\UseCase\Training\GetWorkoutStatsUseCase;
use App\UseCase\Training\GetWorkoutUseCase;
use App\UseCase\Training\StartWorkoutUseCase;
use App\UseCase\Training\UncompleteWorkoutSetUseCase;
use App\UseCase\Training\UpdateWorkoutSetUseCase;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * A workout's summary, created with it and kept as its sets are ticked, unticked, corrected and
 * removed, and as the records it holds move. Every assertion re-reads it from the database.
 */
final class WorkoutSummaryTest extends KernelTestCase
{
    use LoadFixturesTrait;
    use SignedInUserTrait;

    private int $aliceId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadFixtures(WorkoutFixtures::class);
        $this->aliceId = $this->getReference(UserFixtures::ALICE, UserDataModel::class)->id ?? 0;
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

    public function testStartingAWorkoutCreatesItsSummary(): void
    {
        $workout = $this->useCase(StartWorkoutUseCase::class)->execute($this->currentUser($this->aliceId), new StartWorkoutDataInput());

        $summary = $this->summaryOf($workout->id);

        self::assertSame(0, $summary->setCount);
        self::assertNull($summary->volumeInKilograms);
        self::assertSame(0, $summary->personalBestCount);
    }

    /** Laid out like a past workout, a copy has done nothing yet: its sets are all still to do. */
    public function testACopyStartsWithAnEmptySummary(): void
    {
        $source = $this->getReference(WorkoutFixtures::ALICE_FINISHED, WorkoutDataModel::class);

        $copy = $this->useCase(CopyWorkoutUseCase::class)->execute($this->currentUser($this->aliceId), $source->id ?? 0);

        self::assertSame(0, $this->summaryOf($copy->workout->id)->setCount);
    }

    public function testASetCountsOnceTickedAndNoLongerOnceUnticked(): void
    {
        $workout = $this->addSet($this->benchWorkout(), new AddWorkoutSetDataInput(reps: 8, weightInKilograms: 50.0));
        self::assertSame(0, $this->summaryOf($workout->id)->setCount);

        $workout = $this->complete($workout, $this->lastSet($workout));
        $summary = $this->summaryOf($workout->id);
        self::assertSame(1, $summary->setCount);
        self::assertSame(400.0, (float) $summary->volumeInKilograms);

        $this->useCase(UncompleteWorkoutSetUseCase::class)->execute($this->currentUser($this->aliceId), $workout->id, $this->lastSet($workout)->id);
        $summary = $this->summaryOf($workout->id);
        self::assertSame(0, $summary->setCount);
        self::assertNull($summary->volumeInKilograms);
    }

    public function testCorrectingADoneSetMovesTheTotals(): void
    {
        $workout = $this->addSet($this->benchWorkout(), new AddWorkoutSetDataInput(reps: 8, weightInKilograms: 50.0));
        $workout = $this->complete($workout, $this->lastSet($workout));

        $this->useCase(UpdateWorkoutSetUseCase::class)->execute(
            $this->currentUser($this->aliceId), $workout->id, $this->lastSet($workout)->id, new UpdateWorkoutSetDataInput(reps: 10, weightInKilograms: 50.0),
        );

        self::assertSame(500.0, (float) $this->summaryOf($workout->id)->volumeInKilograms);
    }

    public function testRemovingASetTakesItOut(): void
    {
        $workout = $this->addSet($this->benchWorkout(), new AddWorkoutSetDataInput(reps: 8, weightInKilograms: 50.0));
        $workout = $this->complete($workout, $this->lastSet($workout));
        $workout = $this->addSet($workout, new AddWorkoutSetDataInput(reps: 6, weightInKilograms: 50.0));
        $workout = $this->complete($workout, $this->lastSet($workout));

        $this->useCase(DeleteWorkoutSetUseCase::class)->execute($this->currentUser($this->aliceId), $workout->id, $this->lastSet($workout)->id);

        $summary = $this->summaryOf($workout->id);
        self::assertSame(1, $summary->setCount);
        self::assertSame(400.0, (float) $summary->volumeInKilograms);
    }

    /** A movement removed from a superset takes its sets with it; a block removed, all of its own. */
    public function testRemovingAMovementOrABlockTakesTheirSetsOut(): void
    {
        $workout = $this->useCase(StartWorkoutUseCase::class)->execute($this->currentUser($this->aliceId), new StartWorkoutDataInput());
        $workout = $this->useCase(AddWorkoutBlockUseCase::class)->execute($this->currentUser($this->aliceId), $workout->id, new AddWorkoutBlockDataInput([
            new AddWorkoutBlockExerciseDataInput($this->movementId(MovementFixtures::BENCH_PRESS_BARBELL)),
            new AddWorkoutBlockExerciseDataInput($this->movementId(MovementFixtures::PUSH_UP)),
        ]));
        $bench = $workout->blocks[0]->exercises[0];
        $pushUp = $workout->blocks[0]->exercises[1];
        $workout = $this->useCase(AddWorkoutSetUseCase::class)->execute($this->currentUser($this->aliceId), $workout->id, $bench->id, new AddWorkoutSetDataInput(reps: 10, weightInKilograms: 40.0));
        $workout = $this->useCase(AddWorkoutSetUseCase::class)->execute($this->currentUser($this->aliceId), $workout->id, $pushUp->id, new AddWorkoutSetDataInput(reps: 15));
        foreach ($workout->blocks[0]->exercises as $exercise) {
            $workout = $this->useCase(CompleteWorkoutSetUseCase::class)->execute($this->currentUser($this->aliceId), $workout->id, $exercise->sets[0]->id);
        }
        self::assertSame(2, $this->summaryOf($workout->id)->setCount);

        $workout = $this->useCase(DeleteWorkoutExerciseUseCase::class)->execute($this->currentUser($this->aliceId), $workout->id, $pushUp->id);
        $summary = $this->summaryOf($workout->id);
        self::assertSame(1, $summary->setCount);
        self::assertSame(400.0, (float) $summary->volumeInKilograms);

        $this->useCase(DeleteWorkoutBlockUseCase::class)->execute($this->currentUser($this->aliceId), $workout->id, $workout->blocks[0]->id);
        $summary = $this->summaryOf($workout->id);
        self::assertSame(0, $summary->setCount);
        self::assertNull($summary->volumeInKilograms);
    }

    public function testTheRecordsASetBeatsAreCountedAndHandedBack(): void
    {
        $workout = $this->addSet($this->benchWorkout(), new AddWorkoutSetDataInput(reps: 5, weightInKilograms: 70.0));
        $workout = $this->complete($workout, $this->lastSet($workout));
        $beaten = count($this->lastSet($workout)->personalBests);
        self::assertGreaterThan(0, $beaten);
        self::assertSame($beaten, $this->summaryOf($workout->id)->personalBestCount);

        $this->useCase(UncompleteWorkoutSetUseCase::class)->execute($this->currentUser($this->aliceId), $workout->id, $this->lastSet($workout)->id);

        self::assertSame(0, $this->summaryOf($workout->id)->personalBestCount);
    }

    /**
     * A record taken back by an older workout leaves the newer one: its count moves although none
     * of its own sets did.
     */
    public function testARecordTakenBackByAnotherWorkoutLeavesTheCount(): void
    {
        $workout = $this->addSet($this->benchWorkout(), new AddWorkoutSetDataInput(reps: 8, weightInKilograms: 70.0));
        $workout = $this->complete($workout, $this->lastSet($workout));
        $before = $this->summaryOf($workout->id)->personalBestCount;
        self::assertGreaterThan(0, $before);

        // The seeded workout's heaviest bench set, corrected up past the new one.
        $seededId = $this->getReference(WorkoutFixtures::ALICE_FINISHED, WorkoutDataModel::class)->id ?? 0;
        $seeded = $this->useCase(GetWorkoutUseCase::class)->execute($this->currentUser($this->aliceId), $seededId);
        $this->useCase(UpdateWorkoutSetUseCase::class)->execute(
            $this->currentUser($this->aliceId), $seededId, $seeded->blocks[0]->exercises[0]->sets[1]->id, new UpdateWorkoutSetDataInput(reps: 8, weightInKilograms: 90.0),
        );

        self::assertLessThan($before, $this->summaryOf($workout->id)->personalBestCount);
    }

    /** Read back from the database, not from what the request holds in memory. */
    private function summaryOf(int $workoutId): WorkoutSummaryDataModel
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->clear();

        $summary = self::getContainer()->get(WorkoutSummaryProviderGateway::class)->findOneForWorkout(
            $entityManager->getReference(WorkoutDataModel::class, $workoutId),
        );
        if (null === $summary) {
            throw new LogicException('The workout has no summary.');
        }

        return $summary;
    }

    private function benchWorkout(): WorkoutDataOutput
    {
        $workout = $this->useCase(StartWorkoutUseCase::class)->execute($this->currentUser($this->aliceId), new StartWorkoutDataInput());

        return $this->useCase(AddWorkoutBlockUseCase::class)->execute(
            $this->currentUser($this->aliceId), $workout->id, new AddWorkoutBlockDataInput([new AddWorkoutBlockExerciseDataInput($this->movementId(MovementFixtures::BENCH_PRESS_BARBELL))]),
        );
    }

    private function addSet(WorkoutDataOutput $workout, AddWorkoutSetDataInput $input): WorkoutDataOutput
    {
        return $this->useCase(AddWorkoutSetUseCase::class)->execute($this->currentUser($this->aliceId), $workout->id, $workout->blocks[0]->exercises[0]->id, $input);
    }

    private function complete(WorkoutDataOutput $workout, WorkoutSetDataOutput $set): WorkoutDataOutput
    {
        return $this->useCase(CompleteWorkoutSetUseCase::class)->execute($this->currentUser($this->aliceId), $workout->id, $set->id);
    }

    private function lastSet(WorkoutDataOutput $workout): WorkoutSetDataOutput
    {
        $sets = $workout->blocks[0]->exercises[0]->sets;
        if ([] === $sets) {
            throw new LogicException('No set on the first movement.');
        }

        return $sets[array_key_last($sets)];
    }

    private function movementId(string $reference): int
    {
        return $this->getReference($reference, MovementDataModel::class)->id ?? 0;
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    private function useCase(string $class): object
    {
        return self::getContainer()->get($class);
    }
}
