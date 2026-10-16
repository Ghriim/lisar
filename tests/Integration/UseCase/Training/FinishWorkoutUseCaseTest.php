<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Habits\CreateHabitDataInput;
use App\Domain\DTO\Input\Training\AddWorkoutSetDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Registry\Habits\HabitIconRegistry;
use App\Domain\Registry\Habits\HabitSourceRegistry;
use App\Domain\Registry\Habits\HabitTrackerRegistry;
use App\Domain\Registry\Training\PersonalBestKindRegistry as Kind;
use App\Domain\Validation\Constraint\Training\WorkoutFinishableConstraint;
use App\Domain\Validation\Validator\Training\FinishWorkoutValidator;
use App\Fixtures\Training\MovementFixtures;
use App\Tests\Integration\QueryCountTrait;
use App\UseCase\Habits\Admin\CreateHabitUseCase;
use App\UseCase\Habits\ListHabitsUseCase;
use App\UseCase\Habits\SubscribeHabitUseCase;
use App\UseCase\Training\CompleteWorkoutSetUseCase;
use App\UseCase\Training\DeleteWorkoutUseCase;
use App\UseCase\Training\FinishWorkoutUseCase;
use App\UseCase\Training\GetCurrentWorkoutUseCase;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Finishing the workout in progress: every set done, it lands in the history and keeps the workout habit. */
final class FinishWorkoutUseCaseTest extends KernelTestCase
{
    use QueryCountTrait;
    use WorkoutTestTrait;

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

    public function testFinishingKeepsTheWorkoutHabitAndDeletingUnkeepsIt(): void
    {
        $habit = $this->useCase(CreateHabitUseCase::class)->execute($this->currentAdmin(), new CreateHabitDataInput(
            'Une séance', HabitIconRegistry::DUMBBELL, HabitSourceRegistry::TRACKER, HabitTrackerRegistry::WORKOUT, 1,
        ));
        $this->useCase(SubscribeHabitUseCase::class)->execute($this->currentUser($this->idOf($this->alice)), $habit->id);

        $workout = $this->addDoneSet($this->addBlock($this->start(), MovementFixtures::PUSH_UP), new AddWorkoutSetDataInput(reps: 10));
        self::assertFalse($this->isKeptToday($habit->id));

        $this->finish($workout);
        self::assertTrue($this->isKeptToday($habit->id));

        $this->useCase(DeleteWorkoutUseCase::class)->execute($this->currentUser($this->idOf($this->alice)), $workout->id);
        self::assertFalse($this->isKeptToday($habit->id));
    }

    /** Finishing gives a workout its length, and the length can be a record. */
    public function testFinishingAWorkoutCanSetTheLongestOne(): void
    {
        $workout = $this->benchWorkout();
        $workout = $this->addSet($workout, new AddWorkoutSetDataInput(reps: 5, weightInKilograms: 50.0));
        $workout = $this->complete($workout, $this->lastSet($workout));

        $this->useCase(FinishWorkoutUseCase::class)->execute($this->currentUser($this->aliceId), $workout->id);

        // Started and finished in the same second: no longer than the seeded hour.
        self::assertSame(3600.0, $this->session($this->board(), Kind::LONGEST_SESSION)?->current->value);
        self::assertCount(1, $this->session($this->board(), Kind::LONGEST_SESSION)?->progression ?? []);
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

    private function isKeptToday(int $habitId): bool
    {
        foreach ($this->useCase(ListHabitsUseCase::class)->execute($this->currentUser($this->idOf($this->alice))) as $habit) {
            if ($habitId === $habit->habitId) {
                return $habit->isCompletedToday;
            }
        }

        throw new LogicException('The habit is not in the list.');
    }
}
