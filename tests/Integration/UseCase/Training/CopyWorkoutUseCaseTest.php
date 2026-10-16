<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training;

use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Domain\Validation\Constraint\Training\WorkoutNotInProgressConstraint;
use App\Domain\Validation\Validator\Training\CopyWorkoutValidator;
use App\Fixtures\Training\MovementFixtures;
use App\Fixtures\Training\SetTypeFixtures;
use App\Fixtures\Training\WorkoutFixtures;
use App\Tests\Integration\QueryCountTrait;
use App\UseCase\Training\Admin\DeactivateMovementUseCase;
use App\UseCase\Training\Admin\DeactivateSetTypeUseCase;
use App\UseCase\Training\CopyWorkoutUseCase;
use App\UseCase\Training\GetCurrentWorkoutUseCase;
use App\UseCase\Training\GetWorkoutUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Starting a workout from a past one: the same layout, every set still to do. */
final class CopyWorkoutUseCaseTest extends KernelTestCase
{
    use QueryCountTrait;
    use WorkoutTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadWorkoutFixtures();
    }

    /** The seeded « Push » done again: same layout, every set still to do, its day left behind. */
    public function testItStartsAWorkoutFromAPastOne(): void
    {
        $output = $this->copy(WorkoutFixtures::ALICE_FINISHED);
        $copy = $output->workout;

        self::assertSame([], $output->skippedMovements);
        self::assertSame('Push', $copy->name);
        self::assertNull($copy->feeling);
        self::assertTrue($copy->isInProgress);
        self::assertSame(['Bench press (barbell)'], $this->movementNames($copy, 0));
        self::assertSame(['Push-up', 'Farmer walk (dumbbell)'], $this->movementNames($copy, 1));

        $bench = $copy->blocks[0]->exercises[0]->sets;
        self::assertSame([10, 8, 8, 7], array_map(static fn ($set) => $set->reps, $bench));
        self::assertSame([40.0, 60.0, 60.0, 60.0], array_map(static fn ($set) => $set->weightInKilograms, $bench));
        self::assertSame('Échauffement', $bench[0]->setType->name);
        self::assertSame(7.5, $bench[1]->rpe);
        self::assertSame([24.0, 24.0], array_map(static fn ($set) => $set->weightInKilograms, $copy->blocks[1]->exercises[1]->sets));
        // Each movement's rest comes along, and so does a movement's lack of one.
        self::assertSame(150, $copy->blocks[0]->exercises[0]->restInSeconds);
        self::assertNull($copy->blocks[1]->exercises[0]->restInSeconds);
        self::assertSame(90, $copy->blocks[1]->exercises[1]->restInSeconds);

        // Read back: it is the workout in progress, and nothing in it is done yet.
        $current = $this->useCase(GetCurrentWorkoutUseCase::class)->execute($this->currentUser($this->idOf($this->alice)));
        self::assertSame($copy->id, $current?->id);
        foreach ($current->blocks as $block) {
            foreach ($block->exercises as $exercise) {
                foreach ($exercise->sets as $set) {
                    self::assertFalse($set->isComplete);
                }
            }
        }

        // The workout it came from does not move.
        $source = $this->useCase(GetWorkoutUseCase::class)->execute(
            $this->currentUser($this->idOf($this->alice)), $this->getReference(WorkoutFixtures::ALICE_FINISHED, WorkoutDataModel::class)->id ?? 0,
        );
        self::assertFalse($source->isInProgress);
        self::assertSame(4, $source->feeling);
        self::assertCount(4, $source->blocks[0]->exercises[0]->sets);
    }

    public function testACopyLeavesOutWhatIsNoLongerOffered(): void
    {
        $this->useCase(DeactivateMovementUseCase::class)->execute($this->currentAdmin(), $this->movementId(MovementFixtures::PUSH_UP));
        $this->useCase(DeactivateSetTypeUseCase::class)->execute($this->currentAdmin(), $this->setTypeId(SetTypeFixtures::WARM_UP));

        $output = $this->copy(WorkoutFixtures::ALICE_FINISHED);

        self::assertSame(['Push-up'], $output->skippedMovements);
        self::assertSame(['Farmer walk (dumbbell)'], $this->movementNames($output->workout, 1));
        // The warm-up set comes back as an ordinary working set: the default type.
        self::assertSame('Travail', $output->workout->blocks[0]->exercises[0]->sets[0]->setType->name);
    }

    public function testACopyIsRefusedWhileAWorkoutIsInProgress(): void
    {
        $this->start();

        try {
            $this->copy(WorkoutFixtures::ALICE_FINISHED);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(CopyWorkoutValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame([WorkoutNotInProgressConstraint::ALREADY_IN_PROGRESS], $exception->violations['workout']);
        }
    }

    /** The one in progress is the one in the way: only a finished workout is done again. */
    public function testTheWorkoutInProgressCannotBeCopied(): void
    {
        $workout = $this->start();

        try {
            $this->useCase(CopyWorkoutUseCase::class)->execute($this->currentUser($this->idOf($this->alice)), $workout->id);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame([WorkoutNotInProgressConstraint::ALREADY_IN_PROGRESS], $exception->violations['workout']);
        }
    }

    public function testSomeoneElsesWorkoutCannotBeCopied(): void
    {
        $workout = $this->getReference(WorkoutFixtures::ALICE_FINISHED, WorkoutDataModel::class);

        try {
            $this->useCase(CopyWorkoutUseCase::class)->execute($this->currentUser($this->idOf($this->bob)), $workout->id ?? 0);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(['currentUser' => [IsOwnerConstraint::NOT_ALLOWED]], $exception->violations);
        }
    }

    /** Laid out like a past workout, a copy has done nothing yet: its sets are all still to do. */
    public function testACopyStartsWithAnEmptySummary(): void
    {
        $source = $this->getReference(WorkoutFixtures::ALICE_FINISHED, WorkoutDataModel::class);

        $copy = $this->useCase(CopyWorkoutUseCase::class)->execute($this->currentUser($this->aliceId), $source->id ?? 0);

        self::assertSame(0, $this->summaryOf($copy->workout->id)->setCount);
    }

    public function testCopyingAWorkoutStaysWithinItsQueryBudget(): void
    {
        $workoutId = $this->getReference(WorkoutFixtures::ALICE_FINISHED, WorkoutDataModel::class)->id ?? 0;

        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($this->idOf($this->alice)),
            fn (UserDataModel $currentUser) => $this->useCase(CopyWorkoutUseCase::class)->execute($currentUser, $workoutId),
        );
    }
}
