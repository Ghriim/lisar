<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Event\Training\WorkoutBlockDeletedEvent;
use App\Fixtures\Training\MovementFixtures;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\RecordedEventsTrait;
use App\UseCase\Training\DeleteWorkoutBlockUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Removing a whole block from a workout, its movements and their sets with it. */
final class DeleteWorkoutBlockUseCaseTest extends KernelTestCase
{
    use QueryCountTrait;
    use WorkoutTestTrait;
    use RecordedEventsTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadWorkoutFixtures();
    }

    public function testItRemovesAWholeBlock(): void
    {
        $workout = $this->addBlock($this->start(), MovementFixtures::PUSH_UP, MovementFixtures::FARMER_WALK_DUMBBELL);

        $output = $this->useCase(DeleteWorkoutBlockUseCase::class)->execute($this->currentUser($this->idOf($this->alice)), $workout->id, $workout->blocks[0]->id);

        self::assertSame([], $output->blocks);
    }

    /** What the block's sets counted for is handed back by its handlers: the event names its movements. */
    public function testRemovingABlockRaisesItsEvent(): void
    {
        $workout = $this->addBlock($this->start(), MovementFixtures::PUSH_UP, MovementFixtures::FARMER_WALK_DUMBBELL);
        $this->forgetRaisedEvents();

        $this->useCase(DeleteWorkoutBlockUseCase::class)->execute($this->currentUser($this->idOf($this->alice)), $workout->id, $workout->blocks[0]->id);

        $event = $this->assertEventRaised(WorkoutBlockDeletedEvent::class);
        self::assertSame($workout->id, $event->workout->id);
        self::assertSame(
            [$this->movementId(MovementFixtures::PUSH_UP), $this->movementId(MovementFixtures::FARMER_WALK_DUMBBELL)],
            array_map(static fn ($movement) => $movement->id, $event->movements),
        );
        // No set logged in it: there is no record to hand back.
        self::assertFalse($event->heldCompleteSets);
    }

    public function testRemovingABlockStaysWithinItsQueryBudget(): void
    {
        $workout = $this->inProgressPush();
        $blockId = $workout->blocks[1]->id;

        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($this->idOf($this->alice)),
            fn (UserDataModel $currentUser) => $this->useCase(DeleteWorkoutBlockUseCase::class)->execute($currentUser, $workout->id, $blockId),
        );
    }
}
