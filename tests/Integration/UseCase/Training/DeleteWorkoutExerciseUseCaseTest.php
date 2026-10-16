<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Event\Training\WorkoutBlockDeletedEvent;
use App\Domain\DTO\Event\Training\WorkoutExerciseDeletedEvent;
use App\Fixtures\Training\MovementFixtures;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\RecordedEventsTrait;
use App\UseCase\Training\DeleteWorkoutExerciseUseCase;
use App\UseCase\Training\GetWorkoutUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Removing a movement from a workout, its sets with it. What they counted for, its handlers hand back. */
final class DeleteWorkoutExerciseUseCaseTest extends KernelTestCase
{
    use QueryCountTrait;
    use WorkoutTestTrait;
    use RecordedEventsTrait;

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

    /** What the movement's sets counted for is handed back by its handlers. */
    public function testRemovingAMovementRaisesItsEvent(): void
    {
        $workout = $this->addBlock($this->start(), MovementFixtures::BENCH_PRESS_BARBELL, MovementFixtures::PUSH_UP);
        $this->forgetRaisedEvents();

        $this->useCase(DeleteWorkoutExerciseUseCase::class)->execute($this->currentUser($this->aliceId), $workout->id, $workout->blocks[0]->exercises[1]->id);

        $event = $this->assertEventRaised(WorkoutExerciseDeletedEvent::class);
        self::assertSame($workout->id, $event->workout->id);
        self::assertSame($this->movementId(MovementFixtures::PUSH_UP), $event->movement->id);
    }

    /** Its last movement removed, the block goes: that is a block removed, with its movements. */
    public function testRemovingTheLastMovementRaisesTheBlocksEvent(): void
    {
        $workout = $this->addBlock($this->start(), MovementFixtures::PUSH_UP);
        $this->forgetRaisedEvents();

        $this->useCase(DeleteWorkoutExerciseUseCase::class)->execute($this->currentUser($this->aliceId), $workout->id, $workout->blocks[0]->exercises[0]->id);

        $event = $this->assertEventRaised(WorkoutBlockDeletedEvent::class);
        self::assertSame([$this->movementId(MovementFixtures::PUSH_UP)], array_map(static fn ($movement) => $movement->id, $event->movements));
        $this->assertNoEventRaised(WorkoutExerciseDeletedEvent::class);
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
