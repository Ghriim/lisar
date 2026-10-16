<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Event\Training\WorkoutSetUpdatedEvent;
use App\Domain\DTO\Input\Training\AddWorkoutSetDataInput;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\RecordedEventsTrait;
use App\UseCase\Training\CompleteWorkoutSetUseCase;
use App\UseCase\Training\UncompleteWorkoutSetUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Unticking a set: it is to do again, and counts for nothing until ticked anew — which its handlers see to. */
final class UncompleteWorkoutSetUseCaseTest extends KernelTestCase
{
    use QueryCountTrait;
    use WorkoutTestTrait;
    use RecordedEventsTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadWorkoutFixtures();
    }

    /** What an unticked set hands back — its records, its share of the summary — is its handlers' to test. */
    public function testUntickingASetRaisesItsEventOnce(): void
    {
        $workout = $this->addDoneSet($this->benchWorkout(), new AddWorkoutSetDataInput(reps: 5, weightInKilograms: 70.0));
        $this->forgetRaisedEvents();

        $untick = fn () => $this->useCase(UncompleteWorkoutSetUseCase::class)->execute($this->currentUser($this->aliceId), $workout->id, $this->lastSet($workout)->id);
        $untick();

        $event = $this->assertEventRaised(WorkoutSetUpdatedEvent::class);
        self::assertSame($this->lastSet($workout)->id, $event->set->id);
        self::assertFalse($event->set->isComplete);

        // To do already: nothing moves, nothing is said.
        $this->forgetRaisedEvents();
        $untick();
        $this->assertNoEventRaised();
    }

    public function testUntickingASetStaysWithinItsQueryBudget(): void
    {
        $workout = $this->inProgressPush();
        $setId = $workout->blocks[0]->exercises[0]->sets[1]->id;
        $this->useCase(CompleteWorkoutSetUseCase::class)->execute($this->currentUser($this->idOf($this->alice)), $workout->id, $setId);

        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($this->idOf($this->alice)),
            fn (UserDataModel $currentUser) => $this->useCase(UncompleteWorkoutSetUseCase::class)->execute($currentUser, $workout->id, $setId),
        );
    }
}
