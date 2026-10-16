<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\AddWorkoutBlockDataInput;
use App\Domain\DTO\Input\Training\AddWorkoutBlockExerciseDataInput;
use App\Domain\DTO\Input\Training\AddWorkoutSetDataInput;
use App\Domain\DTO\Input\Training\StartWorkoutDataInput;
use App\Fixtures\Training\MovementFixtures;
use App\Tests\Integration\QueryCountTrait;
use App\UseCase\Training\AddWorkoutBlockUseCase;
use App\UseCase\Training\AddWorkoutSetUseCase;
use App\UseCase\Training\CompleteWorkoutSetUseCase;
use App\UseCase\Training\DeleteWorkoutBlockUseCase;
use App\UseCase\Training\DeleteWorkoutExerciseUseCase;
use App\UseCase\Training\GetWorkoutUseCase;
use App\UseCase\Training\StartWorkoutUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Removing a movement from a workout, its sets with it. */
final class DeleteWorkoutExerciseUseCaseTest extends KernelTestCase
{
    use QueryCountTrait;
    use WorkoutTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadWorkoutFixtures();
    }

    /** A block never stays empty: its last movement takes it along. */
    public function testRemovingTheLastMovementOfABlockRemovesTheBlock(): void
    {
        $workout = $this->addBlock($this->start(), MovementFixtures::PUSH_UP, MovementFixtures::FARMER_WALK_DUMBBELL);
        $exercises = $workout->blocks[0]->exercises;

        $output = $this->useCase(DeleteWorkoutExerciseUseCase::class)->execute($this->currentUser($this->idOf($this->alice)), $workout->id, $exercises[0]->id);
        self::assertSame(['Farmer walk (dumbbell)'], $this->movementNames($output, 0));

        $output = $this->useCase(DeleteWorkoutExerciseUseCase::class)->execute($this->currentUser($this->idOf($this->alice)), $workout->id, $exercises[1]->id);
        self::assertSame([], $output->blocks);
        self::assertSame([], $this->useCase(GetWorkoutUseCase::class)->execute($this->currentUser($this->idOf($this->alice)), $workout->id)->blocks);
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

    public function testRemovingAMovementStaysWithinItsQueryBudget(): void
    {
        $workout = $this->inProgressPush();
        $exerciseId = $workout->blocks[1]->exercises[1]->id;

        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($this->idOf($this->alice)),
            fn (UserDataModel $currentUser) => $this->useCase(DeleteWorkoutExerciseUseCase::class)->execute($currentUser, $workout->id, $exerciseId),
        );
    }
}
