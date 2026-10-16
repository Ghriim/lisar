<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training;

use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Event\Training\WorkoutSetUpdatedEvent;
use App\Domain\DTO\Input\Training\AddWorkoutSetDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Training\WorkoutInProgressConstraint;
use App\Domain\Validation\Validator\Training\CompleteWorkoutSetValidator;
use App\Domain\Validation\Validator\Training\UncompleteWorkoutSetValidator;
use App\Fixtures\Training\MovementFixtures;
use App\Fixtures\Training\WorkoutFixtures;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\RecordedEventsTrait;
use App\UseCase\Training\CompleteWorkoutSetUseCase;
use App\UseCase\Training\GetWorkoutUseCase;
use App\UseCase\Training\UncompleteWorkoutSetUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Ticking a set as done: from then on it counts, which its handlers see to. */
final class CompleteWorkoutSetUseCaseTest extends KernelTestCase
{
    use QueryCountTrait;
    use WorkoutTestTrait;
    use RecordedEventsTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadWorkoutFixtures();
    }

    /** During a workout a set is logged before it is done, and ticked once it is. */
    public function testASetIsLoggedUndoneThenTicked(): void
    {
        $workout = $this->addSet($this->addBlock($this->start(), MovementFixtures::PUSH_UP), new AddWorkoutSetDataInput(reps: 10));
        $setId = $workout->blocks[0]->exercises[0]->sets[0]->id;
        self::assertFalse($workout->blocks[0]->exercises[0]->sets[0]->isComplete);

        $this->useCase(CompleteWorkoutSetUseCase::class)->execute($this->currentUser($this->idOf($this->alice)), $workout->id, $setId);
        // Ticking twice changes nothing.
        $this->useCase(CompleteWorkoutSetUseCase::class)->execute($this->currentUser($this->idOf($this->alice)), $workout->id, $setId);

        // Read back from the database, not from what the use case held.
        $reread = $this->useCase(GetWorkoutUseCase::class)->execute($this->currentUser($this->idOf($this->alice)), $workout->id);
        self::assertTrue($reread->blocks[0]->exercises[0]->sets[0]->isComplete);

        $output = $this->useCase(UncompleteWorkoutSetUseCase::class)->execute($this->currentUser($this->idOf($this->alice)), $workout->id, $setId);
        self::assertFalse($output->blocks[0]->exercises[0]->sets[0]->isComplete);
    }

    public function testAFinishedWorkoutHasNothingToTickOrUntick(): void
    {
        $workout = $this->getReference(WorkoutFixtures::ALICE_FINISHED, WorkoutDataModel::class);
        $output = $this->useCase(GetWorkoutUseCase::class)->execute($this->currentUser($this->idOf($this->alice)), $workout->id ?? 0);
        $setId = $output->blocks[0]->exercises[0]->sets[0]->id;

        $refusals = [
            CompleteWorkoutSetUseCase::class => CompleteWorkoutSetValidator::ERROR_CODE,
            UncompleteWorkoutSetUseCase::class => UncompleteWorkoutSetValidator::ERROR_CODE,
        ];
        foreach ($refusals as $useCase => $errorCode) {
            try {
                $this->useCase($useCase)->execute($this->currentUser($this->idOf($this->alice)), $output->id, $setId);
                self::fail('Expected ValidationException');
            } catch (ValidationException $exception) {
                self::assertSame($errorCode, $exception->errorCode);
                self::assertSame([WorkoutInProgressConstraint::FINISHED], $exception->violations['workout']);
            }
        }

        $reread = $this->useCase(GetWorkoutUseCase::class)->execute($this->currentUser($this->idOf($this->alice)), $output->id);
        self::assertTrue($reread->blocks[0]->exercises[0]->sets[0]->isComplete);
    }

    /** What a ticked set moves — the records, its workout's summary — is its handlers' to test. */
    public function testTickingASetRaisesItsEventOnce(): void
    {
        $workout = $this->addSet($this->benchWorkout(), new AddWorkoutSetDataInput(reps: 5, weightInKilograms: 70.0));
        $this->forgetRaisedEvents();

        $this->complete($workout, $this->lastSet($workout));

        $event = $this->assertEventRaised(WorkoutSetUpdatedEvent::class);
        self::assertSame($this->lastSet($workout)->id, $event->set->id);
        self::assertTrue($event->set->isComplete);
        // To do until now: it did not count, it does.
        self::assertFalse($event->countedBefore);

        // Ticked already: nothing moves, nothing is said.
        $this->forgetRaisedEvents();
        $this->complete($workout, $this->lastSet($workout));
        $this->assertNoEventRaised();
    }

    public function testTickingASetStaysWithinItsQueryBudget(): void
    {
        $workout = $this->inProgressPush();
        $setId = $workout->blocks[0]->exercises[0]->sets[1]->id;

        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($this->idOf($this->alice)),
            fn (UserDataModel $currentUser) => $this->useCase(CompleteWorkoutSetUseCase::class)->execute($currentUser, $workout->id, $setId),
        );
    }
}
