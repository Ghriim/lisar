<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\AddWorkoutSetDataInput;
use App\Domain\DTO\Output\Training\PersonalBestBoardDataOutput;
use App\Domain\Registry\Training\PersonalBestKindRegistry as Kind;
use App\Fixtures\Training\MovementFixtures;
use App\Tests\Integration\QueryCountTrait;
use App\UseCase\Training\FinishWorkoutUseCase;
use App\UseCase\Training\ListPersonalBestsUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * The personal bests board: every movement on offer with its records, and the records of whole
 * workouts. Alice's seeded workout benched 8 × 60 kg after a 10 × 40 kg warm-up, which counts for
 * nothing.
 */
final class ListPersonalBestsUseCaseTest extends KernelTestCase
{
    use QueryCountTrait;
    use WorkoutTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadWorkoutFixtures();
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

    public function testTheBoardStaysWithinItsQueryBudget(): void
    {
        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($this->aliceId),
            fn (UserDataModel $currentUser) => $this->useCase(ListPersonalBestsUseCase::class)->execute($currentUser),
        );
    }

    /** Heavier bench sets, one workout after the other: each adds rows to the bench's progressions. */
    public function testTheBoardDoesNotGrowWithTheRecords(): void
    {
        [$fewer, $more] = $this->assertQueryCountDoesNotGrow(
            fn () => $this->currentUser($this->aliceId),
            fn (UserDataModel $currentUser) => $this->useCase(ListPersonalBestsUseCase::class)->execute($currentUser),
            function (): void {
                foreach ([70.0, 75.0] as $load) {
                    $workout = $this->addSet($this->benchWorkout(), new AddWorkoutSetDataInput(reps: 5, weightInKilograms: $load));
                    $workout = $this->complete($workout, $this->lastSet($workout));
                    $this->useCase(FinishWorkoutUseCase::class)->execute($this->currentUser($this->aliceId), $workout->id);
                }
            },
        );

        self::assertGreaterThan($this->rowCount($fewer), $this->rowCount($more));
    }

    /** Every row the board shows: each record's progression, of every movement and of whole workouts. */
    private function rowCount(PersonalBestBoardDataOutput $board): int
    {
        $count = 0;
        foreach ([...$board->sessions, ...array_merge(...array_map(static fn ($movement) => $movement->records, $board->movements))] as $record) {
            $count += count($record->progression);
        }

        return $count;
    }
}
