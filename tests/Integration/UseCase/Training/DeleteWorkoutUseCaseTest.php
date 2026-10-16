<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training;

use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Event\Training\WorkoutDeletedEvent;
use App\Domain\DTO\Input\Training\AddWorkoutSetDataInput;
use App\Domain\Gateway\Provider\Training\WorkoutProviderGateway;
use App\Fixtures\Training\MovementFixtures;
use App\Fixtures\Training\WorkoutFixtures;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\RecordedEventsTrait;
use App\UseCase\Training\DeleteWorkoutUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Deleting a workout, in progress or finished. What it counted for — the habit it kept, the records it held — its handlers hand back. */
final class DeleteWorkoutUseCaseTest extends KernelTestCase
{
    use QueryCountTrait;
    use WorkoutTestTrait;
    use RecordedEventsTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadWorkoutFixtures();
    }

    public function testAbandoningDeletesTheWorkoutInProgress(): void
    {
        $workout = $this->addSet($this->addBlock($this->start(), MovementFixtures::PUSH_UP), new AddWorkoutSetDataInput(reps: 10));

        $this->useCase(DeleteWorkoutUseCase::class)->execute($this->currentUser($this->idOf($this->alice)), $workout->id);

        self::assertNull(self::getContainer()->get(WorkoutProviderGateway::class)->findOneById($workout->id));
    }

    /** A deleted workout no longer knows its owner nor its movements: the event says them, read before. */
    public function testDeletingAWorkoutRaisesItsEvent(): void
    {
        $workout = $this->getReference(WorkoutFixtures::ALICE_FINISHED, WorkoutDataModel::class);
        $finishedAt = $workout->finishedAt;

        $this->useCase(DeleteWorkoutUseCase::class)->execute($this->currentUser($this->aliceId), $workout->id ?? 0);

        $event = $this->assertEventRaised(WorkoutDeletedEvent::class);
        self::assertSame($this->aliceId, $event->owner->id);
        self::assertEquals($finishedAt, $event->finishedAt);
        self::assertTrue($event->heldCountingSets);
        self::assertSame(
            [$this->movementId(MovementFixtures::BENCH_PRESS_BARBELL), $this->movementId(MovementFixtures::PUSH_UP), $this->movementId(MovementFixtures::FARMER_WALK_DUMBBELL)],
            array_map(static fn ($movement) => $movement->id, $event->movements),
        );
    }

    public function testDeletingAWorkoutStaysWithinItsQueryBudget(): void
    {
        $workoutId = $this->getReference(WorkoutFixtures::ALICE_FINISHED, WorkoutDataModel::class)->id ?? 0;

        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($this->idOf($this->alice)),
            fn (UserDataModel $currentUser) => $this->useCase(DeleteWorkoutUseCase::class)->execute($currentUser, $workoutId),
        );
    }
}
