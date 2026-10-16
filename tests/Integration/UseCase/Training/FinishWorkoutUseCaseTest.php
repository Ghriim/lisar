<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Event\Training\WorkoutUpdatedEvent;
use App\Domain\DTO\Input\Training\AddWorkoutSetDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Training\WorkoutFinishableConstraint;
use App\Domain\Validation\Validator\Training\FinishWorkoutValidator;
use App\Fixtures\Training\MovementFixtures;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\RecordedEventsTrait;
use App\UseCase\Training\CompleteWorkoutSetUseCase;
use App\UseCase\Training\FinishWorkoutUseCase;
use App\UseCase\Training\GetCurrentWorkoutUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Finishing the workout in progress: every set done, it lands in the history. Its handlers keep the workout habit and the records. */
final class FinishWorkoutUseCaseTest extends KernelTestCase
{
    use QueryCountTrait;
    use WorkoutTestTrait;
    use RecordedEventsTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadWorkoutFixtures();
    }

    public function testAWorkoutWithoutASetIsNotFinished(): void
    {
        $workout = $this->addBlock($this->start(), MovementFixtures::PUSH_UP);

        try {
            $this->finish($workout);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(FinishWorkoutValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame([WorkoutFinishableConstraint::EMPTY], $exception->violations['workout']);
        }
    }

    public function testFinishingClosesItAndFreesTheWayForTheNextOne(): void
    {
        $workout = $this->addDoneSet($this->addBlock($this->start(), MovementFixtures::PUSH_UP), new AddWorkoutSetDataInput(reps: 10));

        $output = $this->finish($workout);

        self::assertFalse($output->isInProgress);
        self::assertNotNull($output->finishedAt);
        self::assertNull($this->useCase(GetCurrentWorkoutUseCase::class)->execute($this->currentUser($this->idOf($this->alice))));
        // Finishing twice changes nothing: its moment is not rewritten.
        self::assertSame($output->finishedAt, $this->finish($workout)->finishedAt);
        self::assertTrue($this->start()->isInProgress);
    }

    public function testASetNotDoneHoldsTheWorkoutOpen(): void
    {
        $workout = $this->addDoneSet($this->addBlock($this->start(), MovementFixtures::PUSH_UP), new AddWorkoutSetDataInput(reps: 10));
        $workout = $this->addSet($workout, new AddWorkoutSetDataInput(reps: 8));

        try {
            $this->finish($workout);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(FinishWorkoutValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame([WorkoutFinishableConstraint::INCOMPLETE_SETS], $exception->violations['workout']);
        }
    }

    public function testFinishingRaisesItsEventOnce(): void
    {
        $workout = $this->addDoneSet($this->addBlock($this->start(), MovementFixtures::PUSH_UP), new AddWorkoutSetDataInput(reps: 10));
        $this->forgetRaisedEvents();

        $this->finish($workout);

        $event = $this->assertEventRaised(WorkoutUpdatedEvent::class);
        self::assertSame($workout->id, $event->workout->id);
        self::assertNotNull($event->workout->finishedAt);
        self::assertTrue($event->finishes);

        // Finished already: nothing moves, nothing is said.
        $this->forgetRaisedEvents();
        $this->finish($workout);
        $this->assertNoEventRaised();
    }

    public function testFinishingAWorkoutStaysWithinItsQueryBudget(): void
    {
        $workout = $this->inProgressPush();
        foreach ($workout->blocks as $block) {
            foreach ($block->exercises as $exercise) {
                foreach ($exercise->sets as $set) {
                    $this->useCase(CompleteWorkoutSetUseCase::class)->execute($this->currentUser($this->idOf($this->alice)), $workout->id, $set->id);
                }
            }
        }

        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($this->idOf($this->alice)),
            fn (UserDataModel $currentUser) => $this->useCase(FinishWorkoutUseCase::class)->execute($currentUser, $workout->id),
        );
    }
}
