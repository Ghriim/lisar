<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training;

use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Event\Training\WorkoutSetDeletedEvent;
use App\Domain\DTO\Input\Training\AddWorkoutSetDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Training\WorkoutSetInWorkoutConstraint;
use App\Fixtures\Training\MovementFixtures;
use App\Fixtures\Training\WorkoutFixtures;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\RecordedEventsTrait;
use App\UseCase\Training\DeleteWorkoutSetUseCase;
use App\UseCase\Training\GetWorkoutUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Removing a set from a workout. What it counted for is handed back by its handlers. */
final class DeleteWorkoutSetUseCaseTest extends KernelTestCase
{
    use QueryCountTrait;
    use WorkoutTestTrait;
    use RecordedEventsTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadWorkoutFixtures();
    }

    public function testSetsComeInTheOrderTheyWereLoggedAndCanBeRemoved(): void
    {
        $workout = $this->addBlock($this->start(), MovementFixtures::PUSH_UP);
        $this->addSet($workout, new AddWorkoutSetDataInput(reps: 15));
        $this->addSet($workout, new AddWorkoutSetDataInput(reps: 12));
        $workout = $this->addSet($workout, new AddWorkoutSetDataInput(reps: 10));

        $sets = $workout->blocks[0]->exercises[0]->sets;
        self::assertSame([15, 12, 10], array_map(static fn ($set) => $set->reps, $sets));

        $output = $this->useCase(DeleteWorkoutSetUseCase::class)->execute($this->currentUser($this->idOf($this->alice)), $workout->id, $sets[1]->id);

        self::assertSame([15, 10], array_map(static fn ($set) => $set->reps, $output->blocks[0]->exercises[0]->sets));
    }

    public function testASetOfAnotherWorkoutIsNotFound(): void
    {
        $seeded = $this->useCase(GetWorkoutUseCase::class)->execute(
            $this->currentUser($this->idOf($this->alice)), $this->getReference(WorkoutFixtures::ALICE_FINISHED, WorkoutDataModel::class)->id ?? 0,
        );
        $workout = $this->start();

        try {
            $this->useCase(DeleteWorkoutSetUseCase::class)->execute($this->currentUser($this->idOf($this->alice)), $workout->id, $seeded->blocks[0]->exercises[0]->sets[0]->id);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(['setId' => [WorkoutSetInWorkoutConstraint::NOT_FOUND]], $exception->violations);
        }
    }

    public function testASetThatDoesNotExistIsNotFound(): void
    {
        $workout = $this->start();

        $this->expectException(DataModelNotFoundException::class);

        $this->useCase(DeleteWorkoutSetUseCase::class)->execute($this->currentUser($this->idOf($this->alice)), $workout->id, 999999);
    }

    /** A removed set no longer knows its workout nor its movement: the event says them, read before. */
    public function testRemovingASetRaisesItsEvent(): void
    {
        $workout = $this->addDoneSet($this->benchWorkout(), new AddWorkoutSetDataInput(reps: 5, weightInKilograms: 70.0));
        $this->forgetRaisedEvents();

        $this->useCase(DeleteWorkoutSetUseCase::class)->execute($this->currentUser($this->aliceId), $workout->id, $this->lastSet($workout)->id);

        $event = $this->assertEventRaised(WorkoutSetDeletedEvent::class);
        self::assertSame($workout->id, $event->workout->id);
        self::assertSame($this->movementId(MovementFixtures::BENCH_PRESS_BARBELL), $event->movement->id);
    }

    public function testRemovingASetStaysWithinItsQueryBudget(): void
    {
        $workout = $this->inProgressPush();
        $setId = $workout->blocks[0]->exercises[0]->sets[1]->id;

        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($this->idOf($this->alice)),
            fn (UserDataModel $currentUser) => $this->useCase(DeleteWorkoutSetUseCase::class)->execute($currentUser, $workout->id, $setId),
        );
    }
}
