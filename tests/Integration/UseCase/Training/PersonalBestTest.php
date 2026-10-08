<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training;

use App\Domain\DTO\DataModel\Training\MovementDataModel;
use App\Domain\DTO\DataModel\Training\SetTypeDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\AddWorkoutBlockDataInput;
use App\Domain\DTO\Input\Training\AddWorkoutBlockExerciseDataInput;
use App\Domain\DTO\Input\Training\AddWorkoutSetDataInput;
use App\Domain\DTO\Input\Training\StartWorkoutDataInput;
use App\Domain\DTO\Input\Training\UpdateSetTypeDataInput;
use App\Domain\DTO\Input\Training\UpdateWorkoutSetDataInput;
use App\Domain\DTO\Output\Training\PersonalBestBoardDataOutput;
use App\Domain\DTO\Output\Training\PersonalBestRecordDataOutput;
use App\Domain\DTO\Output\Training\WorkoutDataOutput;
use App\Domain\DTO\Output\Training\WorkoutSetDataOutput;
use App\Domain\Registry\Training\PersonalBestKindRegistry as Kind;
use App\Domain\Registry\Training\SetTypeColourRegistry;
use App\Fixtures\Training\MovementFixtures;
use App\Fixtures\Training\SetTypeFixtures;
use App\Fixtures\Training\WorkoutFixtures;
use App\Fixtures\User\UserFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\UseCase\Training\AddWorkoutBlockUseCase;
use App\UseCase\Training\AddWorkoutSetUseCase;
use App\UseCase\Training\Admin\UpdateSetTypeUseCase;
use App\UseCase\Training\CompleteWorkoutSetUseCase;
use App\UseCase\Training\DeleteWorkoutSetUseCase;
use App\UseCase\Training\DeleteWorkoutUseCase;
use App\UseCase\Training\FinishWorkoutUseCase;
use App\UseCase\Training\GetWorkoutStatsUseCase;
use App\UseCase\Training\ListPersonalBestsUseCase;
use App\UseCase\Training\StartWorkoutUseCase;
use App\UseCase\Training\UncompleteWorkoutSetUseCase;
use App\UseCase\Training\UpdateWorkoutSetUseCase;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Personal bests kept as sets are ticked, unticked, corrected and removed. Alice's seeded workout
 * benched 8 × 60 kg after a 10 × 40 kg warm-up, which counts for nothing.
 */
final class PersonalBestTest extends KernelTestCase
{
    use LoadFixturesTrait;

    private int $aliceId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadFixtures(WorkoutFixtures::class);
        $this->aliceId = $this->getReference(UserFixtures::ALICE, UserDataModel::class)->id ?? 0;
    }

    /** The fixtures go through the persisters: their sets already set Alice's records. */
    public function testTheSeededWorkoutSetTheFirstRecords(): void
    {
        $board = $this->board();

        self::assertSame(60.0, $this->current($board, MovementFixtures::BENCH_PRESS_BARBELL, Kind::MAX_WEIGHT));
        self::assertSame(76.0, $this->current($board, MovementFixtures::BENCH_PRESS_BARBELL, Kind::ESTIMATED_ONE_REP_MAX));
        self::assertSame(1380.0, $this->current($board, MovementFixtures::BENCH_PRESS_BARBELL, Kind::MAX_WORKOUT_VOLUME));
        self::assertSame(15.0, $this->current($board, MovementFixtures::PUSH_UP, Kind::MAX_REPS));
        self::assertSame(40.0, $this->current($board, MovementFixtures::FARMER_WALK_DUMBBELL, Kind::MAX_DISTANCE_FOR_WEIGHT, 24.0));
        // The warm-up's ten reps would have made a 10-rep record; it counts for nothing.
        self::assertNull($this->record($board, MovementFixtures::BENCH_PRESS_BARBELL, Kind::MAX_WEIGHT_FOR_REPS, 10.0));

        self::assertSame(7.0, $this->session($board, Kind::MAX_SESSION_SETS)?->current->value);
        self::assertSame(3600.0, $this->session($board, Kind::LONGEST_SESSION)?->current->value);
    }

    /** Every movement on offer is listed, with its records or none. */
    public function testTheBoardListsEveryMovementOnOffer(): void
    {
        $names = array_map(static fn ($movement) => $movement->movementName, $this->board()->movements);

        self::assertContains('Bench press (barbell)', $names);
        self::assertContains('Push-up', $names);
        self::assertGreaterThan(3, count($names));
    }

    public function testTickingASetBeatsTheRecordAtOnce(): void
    {
        $workout = $this->benchWorkout();
        $workout = $this->addSet($workout, new AddWorkoutSetDataInput(reps: 5, weightInKilograms: 70.0));

        $output = $this->complete($workout, $this->lastSet($workout));

        self::assertContains(Kind::MAX_WEIGHT, $this->kinds($this->lastSet($output)));
        $record = $this->record($this->board(), MovementFixtures::BENCH_PRESS_BARBELL, Kind::MAX_WEIGHT);
        self::assertSame(70.0, $record?->current->value);
        self::assertSame([60.0, 70.0], array_map(static fn ($row) => $row->value, $record?->progression ?? []));
    }

    /** A set logged but not ticked is still to do: it beats nothing yet. */
    public function testASetNotTickedBeatsNothing(): void
    {
        $workout = $this->addSet($this->benchWorkout(), new AddWorkoutSetDataInput(reps: 5, weightInKilograms: 70.0));

        self::assertSame([], $this->lastSet($workout)->personalBests);
        self::assertSame(60.0, $this->current($this->board(), MovementFixtures::BENCH_PRESS_BARBELL, Kind::MAX_WEIGHT));
    }

    public function testUntickingASetHandsTheRecordBack(): void
    {
        $workout = $this->benchWorkout();
        $workout = $this->addSet($workout, new AddWorkoutSetDataInput(reps: 5, weightInKilograms: 70.0));
        $workout = $this->complete($workout, $this->lastSet($workout));

        $output = $this->useCase(UncompleteWorkoutSetUseCase::class)->execute($this->aliceId, $workout->id, $this->lastSet($workout)->id);

        self::assertSame([], $this->lastSet($output)->personalBests);
        self::assertSame(60.0, $this->current($this->board(), MovementFixtures::BENCH_PRESS_BARBELL, Kind::MAX_WEIGHT));
    }

    public function testCorrectingASetDownHandsTheRecordBack(): void
    {
        $workout = $this->benchWorkout();
        $workout = $this->addSet($workout, new AddWorkoutSetDataInput(reps: 5, weightInKilograms: 70.0));
        $workout = $this->complete($workout, $this->lastSet($workout));

        $this->useCase(UpdateWorkoutSetUseCase::class)->execute(
            $this->aliceId, $workout->id, $this->lastSet($workout)->id, new UpdateWorkoutSetDataInput(reps: 5, weightInKilograms: 50.0),
        );

        self::assertSame(60.0, $this->current($this->board(), MovementFixtures::BENCH_PRESS_BARBELL, Kind::MAX_WEIGHT));
    }

    public function testRemovingASetHandsTheRecordBack(): void
    {
        $workout = $this->benchWorkout();
        $workout = $this->addSet($workout, new AddWorkoutSetDataInput(reps: 5, weightInKilograms: 70.0));
        $workout = $this->complete($workout, $this->lastSet($workout));

        $this->useCase(DeleteWorkoutSetUseCase::class)->execute($this->aliceId, $workout->id, $this->lastSet($workout)->id);

        self::assertSame(60.0, $this->current($this->board(), MovementFixtures::BENCH_PRESS_BARBELL, Kind::MAX_WEIGHT));
    }

    public function testAbandoningAWorkoutHandsItsRecordsBack(): void
    {
        $workout = $this->benchWorkout();
        $workout = $this->addSet($workout, new AddWorkoutSetDataInput(reps: 5, weightInKilograms: 70.0));
        $workout = $this->complete($workout, $this->lastSet($workout));

        $this->useCase(DeleteWorkoutUseCase::class)->execute($this->aliceId, $workout->id);

        $board = $this->board();
        self::assertSame(60.0, $this->current($board, MovementFixtures::BENCH_PRESS_BARBELL, Kind::MAX_WEIGHT));
        self::assertSame(7.0, $this->session($board, Kind::MAX_SESSION_SETS)?->current->value);
    }

    /** A warm-up is light on purpose: however heavy, it sets no record. */
    public function testASetOfATypeThatDoesNotCountBeatsNothing(): void
    {
        $workout = $this->benchWorkout();
        $workout = $this->addSet($workout, new AddWorkoutSetDataInput(reps: 5, weightInKilograms: 100.0, setTypeId: $this->setTypeId(SetTypeFixtures::WARM_UP)));

        $output = $this->complete($workout, $this->lastSet($workout));

        self::assertSame([], $this->lastSet($output)->personalBests);
        self::assertSame(60.0, $this->current($this->board(), MovementFixtures::BENCH_PRESS_BARBELL, Kind::MAX_WEIGHT));
    }

    /** Switching a type to count rebuilds the records of every set carrying it. */
    public function testMakingATypeCountRebuildsTheRecordsOfItsSets(): void
    {
        $this->useCase(UpdateSetTypeUseCase::class)->execute(
            $this->setTypeId(SetTypeFixtures::WARM_UP),
            new UpdateSetTypeDataInput('Échauffement', SetTypeColourRegistry::ORANGE, countsForPersonalBests: true),
        );

        self::assertSame(40.0, $this->current($this->board(), MovementFixtures::BENCH_PRESS_BARBELL, Kind::MAX_WEIGHT_FOR_REPS, 10.0));
    }

    /** Finishing gives a workout its length, and the length can be a record. */
    public function testFinishingAWorkoutCanSetTheLongestOne(): void
    {
        $workout = $this->benchWorkout();
        $workout = $this->addSet($workout, new AddWorkoutSetDataInput(reps: 5, weightInKilograms: 50.0));
        $workout = $this->complete($workout, $this->lastSet($workout));

        $this->useCase(FinishWorkoutUseCase::class)->execute($this->aliceId, $workout->id);

        // Started and finished in the same second: no longer than the seeded hour.
        self::assertSame(3600.0, $this->session($this->board(), Kind::LONGEST_SESSION)?->current->value);
        self::assertCount(1, $this->session($this->board(), Kind::LONGEST_SESSION)?->progression ?? []);
    }

    /** The bilan lists what the workout beat. */
    public function testTheBilanListsTheRecordsTheWorkoutBeat(): void
    {
        $workout = $this->getReference(WorkoutFixtures::ALICE_FINISHED, WorkoutDataModel::class);

        $stats = $this->useCase(GetWorkoutStatsUseCase::class)->execute($this->aliceId, $workout->id ?? 0);

        $kinds = array_map(static fn ($record) => $record->kind, $stats->personalBests);
        self::assertContains(Kind::MAX_WEIGHT, $kinds);
        self::assertContains(Kind::MAX_SESSION_SETS, $kinds);
        self::assertSame(Kind::MAX_WEIGHT, $stats->personalBests[0]->kind);
    }

    private function board(): PersonalBestBoardDataOutput
    {
        return $this->useCase(ListPersonalBestsUseCase::class)->execute($this->aliceId);
    }

    private function record(PersonalBestBoardDataOutput $board, string $movementReference, string $kind, ?float $tier = null): ?PersonalBestRecordDataOutput
    {
        $movementId = $this->getReference($movementReference, MovementDataModel::class)->id;
        foreach ($board->movements as $movement) {
            if ($movementId !== $movement->movementId) {
                continue;
            }
            foreach ($movement->records as $record) {
                if ($kind === $record->kind && $tier === $record->tier) {
                    return $record;
                }
            }
        }

        return null;
    }

    private function current(PersonalBestBoardDataOutput $board, string $movementReference, string $kind, ?float $tier = null): ?float
    {
        return $this->record($board, $movementReference, $kind, $tier)?->current->value;
    }

    private function session(PersonalBestBoardDataOutput $board, string $kind): ?PersonalBestRecordDataOutput
    {
        foreach ($board->sessions as $record) {
            if ($kind === $record->kind) {
                return $record;
            }
        }

        return null;
    }

    /** @return list<string> */
    private function kinds(WorkoutSetDataOutput $set): array
    {
        return array_map(static fn ($record) => $record->kind, $set->personalBests);
    }

    private function benchWorkout(): WorkoutDataOutput
    {
        $workout = $this->useCase(StartWorkoutUseCase::class)->execute($this->aliceId, new StartWorkoutDataInput());
        $benchId = $this->getReference(MovementFixtures::BENCH_PRESS_BARBELL, MovementDataModel::class)->id ?? 0;

        return $this->useCase(AddWorkoutBlockUseCase::class)->execute($this->aliceId, $workout->id, new AddWorkoutBlockDataInput([new AddWorkoutBlockExerciseDataInput($benchId)]));
    }

    private function addSet(WorkoutDataOutput $workout, AddWorkoutSetDataInput $input): WorkoutDataOutput
    {
        return $this->useCase(AddWorkoutSetUseCase::class)->execute($this->aliceId, $workout->id, $workout->blocks[0]->exercises[0]->id, $input);
    }

    private function complete(WorkoutDataOutput $workout, WorkoutSetDataOutput $set): WorkoutDataOutput
    {
        return $this->useCase(CompleteWorkoutSetUseCase::class)->execute($this->aliceId, $workout->id, $set->id);
    }

    private function lastSet(WorkoutDataOutput $workout): WorkoutSetDataOutput
    {
        $sets = $workout->blocks[0]->exercises[0]->sets;
        if ([] === $sets) {
            throw new LogicException('No set on the first movement.');
        }

        return $sets[count($sets) - 1];
    }

    private function setTypeId(string $reference): int
    {
        return $this->getReference($reference, SetTypeDataModel::class)->id ?? 0;
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
