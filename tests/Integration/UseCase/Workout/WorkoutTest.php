<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Workout;

use App\Domain\DTO\DataModel\MovementDataModel;
use App\Domain\DTO\DataModel\SetTypeDataModel;
use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\DTO\DataModel\WorkoutDataModel;
use App\Domain\DTO\Input\Habit\CreateHabitDataInput;
use App\Domain\DTO\Input\Workout\AddWorkoutBlockDataInput;
use App\Domain\DTO\Input\Workout\AddWorkoutExerciseDataInput;
use App\Domain\DTO\Input\Workout\AddWorkoutSetDataInput;
use App\Domain\DTO\Input\Workout\ListWorkoutsDataInput;
use App\Domain\DTO\Input\Workout\ReorderWorkoutBlocksDataInput;
use App\Domain\DTO\Input\Workout\StartWorkoutDataInput;
use App\Domain\DTO\Input\Workout\UpdateWorkoutDataInput;
use App\Domain\DTO\Input\Workout\UpdateWorkoutExerciseDataInput;
use App\Domain\DTO\Input\Workout\UpdateWorkoutSetDataInput;
use App\Domain\DTO\Output\Workout\WorkoutDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\WorkoutProviderGateway;
use App\Domain\Registry\Habit\HabitIconRegistry;
use App\Domain\Registry\Habit\HabitSourceRegistry;
use App\Domain\Registry\Habit\HabitTrackerRegistry;
use App\Domain\Validation\Constraint\Workout\WorkoutBlockOrderConstraint;
use App\Domain\Validation\Constraint\Workout\WorkoutFinishableConstraint;
use App\Domain\Validation\Constraint\Workout\WorkoutInProgressConstraint;
use App\Domain\Validation\Constraint\Workout\WorkoutMovementsOfferedConstraint;
use App\Domain\Validation\Constraint\Workout\WorkoutNotInProgressConstraint;
use App\Domain\Validation\Constraint\Workout\WorkoutSetMeasuresConstraint;
use App\Domain\Validation\Constraint\Workout\WorkoutSetTypeUsableConstraint;
use App\Domain\Validation\Validator\Workout\AddWorkoutBlockValidator;
use App\Domain\Validation\Validator\Workout\AddWorkoutSetValidator;
use App\Domain\Validation\Validator\Workout\StartWorkoutValidator;
use App\Fixtures\MovementFixtures;
use App\Fixtures\SetTypeFixtures;
use App\Fixtures\UserFixtures;
use App\Fixtures\WorkoutFixtures;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\Tests\Integration\LoadFixturesTrait;
use App\UseCase\Admin\CreateHabitUseCase;
use App\UseCase\Admin\DeactivateMovementUseCase;
use App\UseCase\Admin\DeactivateSetTypeUseCase;
use App\UseCase\Habit\ListHabitsUseCase;
use App\UseCase\Habit\SubscribeHabitUseCase;
use App\UseCase\Workout\AddWorkoutBlockUseCase;
use App\UseCase\Workout\AddWorkoutExerciseUseCase;
use App\UseCase\Workout\AddWorkoutSetUseCase;
use App\UseCase\Workout\CompleteWorkoutSetUseCase;
use App\UseCase\Workout\DeleteWorkoutBlockUseCase;
use App\UseCase\Workout\DeleteWorkoutExerciseUseCase;
use App\UseCase\Workout\DeleteWorkoutSetUseCase;
use App\UseCase\Workout\DeleteWorkoutUseCase;
use App\UseCase\Workout\FinishWorkoutUseCase;
use App\UseCase\Workout\GetCurrentWorkoutUseCase;
use App\UseCase\Workout\GetWorkoutStatsUseCase;
use App\UseCase\Workout\GetWorkoutUseCase;
use App\UseCase\Workout\ListWorkoutMovementsUseCase;
use App\UseCase\Workout\ListWorkoutPreviousPerformancesUseCase;
use App\UseCase\Workout\ListWorkoutSetTypesUseCase;
use App\UseCase\Workout\ListWorkoutsUseCase;
use App\UseCase\Workout\ReorderWorkoutBlocksUseCase;
use App\UseCase\Workout\StartWorkoutUseCase;
use App\UseCase\Workout\UncompleteWorkoutSetUseCase;
use App\UseCase\Workout\UpdateWorkoutExerciseUseCase;
use App\UseCase\Workout\UpdateWorkoutSetUseCase;
use App\UseCase\Workout\UpdateWorkoutUseCase;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * A workout logged live, from start to finish, and everything done to it on the way: blocks,
 * supersets, sets, corrections — then the history it lands in, and the habit it keeps.
 */
final class WorkoutTest extends KernelTestCase
{
    use LoadFixturesTrait;

    private UserDataModel $alice;
    private UserDataModel $bob;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadFixtures(WorkoutFixtures::class);

        $this->alice = $this->getReference(UserFixtures::ALICE, UserDataModel::class);
        $this->bob = $this->getReference(UserFixtures::BOB_DEACTIVATED, UserDataModel::class);
    }

    public function testItStartsAnEmptyWorkoutInProgress(): void
    {
        $output = $this->start('Jambes');

        self::assertSame('Jambes', $output->name);
        self::assertTrue($output->isInProgress);
        self::assertNull($output->finishedAt);
        self::assertSame([], $output->blocks);

        self::assertSame($output->id, $this->useCase(GetCurrentWorkoutUseCase::class)->execute($this->idOf($this->alice))?->id);
    }

    public function testThereIsNoCurrentWorkoutWhenNoneIsInProgress(): void
    {
        self::assertNull($this->useCase(GetCurrentWorkoutUseCase::class)->execute($this->idOf($this->alice)));
    }

    /** A blank name is no name: each front end words its own default. */
    public function testABlankNameIsNone(): void
    {
        self::assertNull($this->start('')->name);
    }

    public function testItRefusesASecondWorkoutInProgress(): void
    {
        $this->start();

        try {
            $this->start();
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(StartWorkoutValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame([WorkoutNotInProgressConstraint::ALREADY_IN_PROGRESS], $exception->violations['workout']);
        }
    }

    public function testItAddsABlockAndASuperset(): void
    {
        $workout = $this->start();

        $this->addBlock($workout, MovementFixtures::BENCH_PRESS_BARBELL);
        $output = $this->addBlock($workout, MovementFixtures::PUSH_UP, MovementFixtures::FARMER_WALK_DUMBBELL);

        self::assertCount(2, $output->blocks);
        self::assertSame(['Bench press (barbell)'], $this->movementNames($output, 0));
        // The superset keeps the order it was given in.
        self::assertSame(['Push-up', 'Farmer walk (dumbbell)'], $this->movementNames($output, 1));
    }

    public function testAMovementJoinsAnExistingBlock(): void
    {
        $workout = $this->addBlock($this->start(), MovementFixtures::BENCH_PRESS_BARBELL);

        $output = $this->useCase(AddWorkoutExerciseUseCase::class)->execute(
            $this->idOf($this->alice), $workout->id, $workout->blocks[0]->id,
            new AddWorkoutExerciseDataInput($this->movementId(MovementFixtures::PUSH_UP)),
        );

        self::assertSame(['Bench press (barbell)', 'Push-up'], $this->movementNames($output, 0));
    }

    public function testTheSameMovementMayComeTwice(): void
    {
        $workout = $this->addBlock($this->start(), MovementFixtures::PUSH_UP);

        $output = $this->addBlock($workout, MovementFixtures::PUSH_UP);

        self::assertCount(2, $output->blocks);
    }

    public function testItRefusesAMovementNoLongerOffered(): void
    {
        $workout = $this->start();
        $this->useCase(DeactivateMovementUseCase::class)->execute($this->movementId(MovementFixtures::PUSH_UP));

        try {
            $this->addBlock($workout, MovementFixtures::BENCH_PRESS_BARBELL, MovementFixtures::PUSH_UP);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(AddWorkoutBlockValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame([WorkoutMovementsOfferedConstraint::UNAVAILABLE], $exception->violations['movementIds']);
        }
    }

    public function testItLogsASetWithTheMeasuresItsMovementTracks(): void
    {
        $workout = $this->addBlock($this->start(), MovementFixtures::BENCH_PRESS_BARBELL);

        $output = $this->addSet($workout, new AddWorkoutSetDataInput(
            reps: 8, weightInKilograms: 62.5, rpe: 8.5, setTypeId: $this->setTypeId(SetTypeFixtures::DROPSET),
        ));

        $set = $output->blocks[0]->exercises[0]->sets[0];
        self::assertSame(8, $set->reps);
        self::assertSame(62.5, $set->weightInKilograms);
        self::assertSame(8.5, $set->rpe);
        self::assertNull($set->durationInSeconds);
        self::assertSame('Dropset', $set->setType?->name);

        // Read back from the database, not from what the use case held.
        $reread = $this->useCase(GetWorkoutUseCase::class)->execute($this->idOf($this->alice), $workout->id);
        self::assertSame(62.5, $reread->blocks[0]->exercises[0]->sets[0]->weightInKilograms);
    }

    public function testASetMissesNoTrackedMeasureAndCarriesNoOther(): void
    {
        $workout = $this->addBlock($this->start(), MovementFixtures::BENCH_PRESS_BARBELL);

        try {
            $this->addSet($workout, new AddWorkoutSetDataInput(reps: 8, distanceInMetres: 100));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(AddWorkoutSetValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame([WorkoutSetMeasuresConstraint::WEIGHT_REQUIRED], $exception->violations['weightInKilograms']);
            self::assertSame([WorkoutSetMeasuresConstraint::DISTANCE_NOT_TRACKED], $exception->violations['distanceInMetres']);
        }
    }

    public function testASetRefusesARetiredSetType(): void
    {
        $workout = $this->addBlock($this->start(), MovementFixtures::PUSH_UP);
        $this->useCase(DeactivateSetTypeUseCase::class)->execute($this->setTypeId(SetTypeFixtures::DROPSET));

        try {
            $this->addSet($workout, new AddWorkoutSetDataInput(reps: 10, setTypeId: $this->setTypeId(SetTypeFixtures::DROPSET)));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame([WorkoutSetTypeUsableConstraint::INACTIVE], $exception->violations['setTypeId']);
        }
    }

    /** Staying is not taking on: a set keeps a set type retired since, through a correction. */
    public function testACorrectedSetKeepsASetTypeRetiredSince(): void
    {
        $workout = $this->addBlock($this->start(), MovementFixtures::PUSH_UP);
        $workout = $this->addSet($workout, new AddWorkoutSetDataInput(reps: 10, setTypeId: $this->setTypeId(SetTypeFixtures::DROPSET)));
        $this->useCase(DeactivateSetTypeUseCase::class)->execute($this->setTypeId(SetTypeFixtures::DROPSET));

        $output = $this->useCase(UpdateWorkoutSetUseCase::class)->execute(
            $this->idOf($this->alice), $workout->id, $workout->blocks[0]->exercises[0]->sets[0]->id,
            new UpdateWorkoutSetDataInput(reps: 12, setTypeId: $this->setTypeId(SetTypeFixtures::DROPSET)),
        );

        $set = $output->blocks[0]->exercises[0]->sets[0];
        self::assertSame(12, $set->reps);
        self::assertSame('Dropset', $set->setType?->name);
    }

    public function testSetsComeInTheOrderTheyWereLoggedAndCanBeRemoved(): void
    {
        $workout = $this->addBlock($this->start(), MovementFixtures::PUSH_UP);
        $this->addSet($workout, new AddWorkoutSetDataInput(reps: 15));
        $this->addSet($workout, new AddWorkoutSetDataInput(reps: 12));
        $workout = $this->addSet($workout, new AddWorkoutSetDataInput(reps: 10));

        $sets = $workout->blocks[0]->exercises[0]->sets;
        self::assertSame([15, 12, 10], array_map(static fn ($set) => $set->reps, $sets));

        $output = $this->useCase(DeleteWorkoutSetUseCase::class)->execute($this->idOf($this->alice), $workout->id, $sets[1]->id);

        self::assertSame([15, 10], array_map(static fn ($set) => $set->reps, $output->blocks[0]->exercises[0]->sets));
    }

    /** A block never stays empty: its last movement takes it along. */
    public function testRemovingTheLastMovementOfABlockRemovesTheBlock(): void
    {
        $workout = $this->addBlock($this->start(), MovementFixtures::PUSH_UP, MovementFixtures::FARMER_WALK_DUMBBELL);
        $exercises = $workout->blocks[0]->exercises;

        $output = $this->useCase(DeleteWorkoutExerciseUseCase::class)->execute($this->idOf($this->alice), $workout->id, $exercises[0]->id);
        self::assertSame(['Farmer walk (dumbbell)'], $this->movementNames($output, 0));

        $output = $this->useCase(DeleteWorkoutExerciseUseCase::class)->execute($this->idOf($this->alice), $workout->id, $exercises[1]->id);
        self::assertSame([], $output->blocks);
        self::assertSame([], $this->useCase(GetWorkoutUseCase::class)->execute($this->idOf($this->alice), $workout->id)->blocks);
    }

    public function testItRemovesAWholeBlock(): void
    {
        $workout = $this->addBlock($this->start(), MovementFixtures::PUSH_UP, MovementFixtures::FARMER_WALK_DUMBBELL);

        $output = $this->useCase(DeleteWorkoutBlockUseCase::class)->execute($this->idOf($this->alice), $workout->id, $workout->blocks[0]->id);

        self::assertSame([], $output->blocks);
    }

    public function testItReordersTheBlocks(): void
    {
        $workout = $this->addBlock($this->start(), MovementFixtures::BENCH_PRESS_BARBELL);
        $workout = $this->addBlock($workout, MovementFixtures::PUSH_UP);

        $output = $this->useCase(ReorderWorkoutBlocksUseCase::class)->execute(
            $this->idOf($this->alice), $workout->id,
            new ReorderWorkoutBlocksDataInput([$workout->blocks[1]->id, $workout->blocks[0]->id]),
        );

        self::assertSame(['Push-up'], $this->movementNames($output, 0));
        $reread = $this->useCase(GetWorkoutUseCase::class)->execute($this->idOf($this->alice), $workout->id);
        self::assertSame(['Push-up'], $this->movementNames($reread, 0));
    }

    public function testANewOrderNamesEveryBlockOnce(): void
    {
        $workout = $this->addBlock($this->start(), MovementFixtures::BENCH_PRESS_BARBELL);
        $workout = $this->addBlock($workout, MovementFixtures::PUSH_UP);

        try {
            $this->useCase(ReorderWorkoutBlocksUseCase::class)->execute(
                $this->idOf($this->alice), $workout->id, new ReorderWorkoutBlocksDataInput([$workout->blocks[1]->id]),
            );
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame([WorkoutBlockOrderConstraint::MISMATCH], $exception->violations['blockIds']);
        }
    }

    public function testItNotesAWorkoutAndAMovement(): void
    {
        $workout = $this->addBlock($this->start(), MovementFixtures::PUSH_UP);

        $this->useCase(UpdateWorkoutExerciseUseCase::class)->execute(
            $this->idOf($this->alice), $workout->id, $workout->blocks[0]->exercises[0]->id, new UpdateWorkoutExerciseDataInput('Coudes serrés'),
        );
        $output = $this->useCase(UpdateWorkoutUseCase::class)->execute(
            $this->idOf($this->alice), $workout->id, new UpdateWorkoutDataInput('Haut du corps', 'Bonne énergie', 5),
        );

        self::assertSame('Haut du corps', $output->name);
        self::assertSame('Bonne énergie', $output->note);
        self::assertSame(5, $output->feeling);
        self::assertSame('Coudes serrés', $output->blocks[0]->exercises[0]->note);
        self::assertSame($workout->startedAt, $output->startedAt);
    }

    public function testAWorkoutWithoutASetIsNotFinished(): void
    {
        $workout = $this->addBlock($this->start(), MovementFixtures::PUSH_UP);

        try {
            $this->finish($workout);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(FinishWorkoutUseCase::ERROR_CODE, $exception->errorCode);
            self::assertSame([WorkoutFinishableConstraint::EMPTY], $exception->violations['workout']);
        }
    }

    public function testFinishingClosesItAndFreesTheWayForTheNextOne(): void
    {
        $workout = $this->addDoneSet($this->addBlock($this->start(), MovementFixtures::PUSH_UP), new AddWorkoutSetDataInput(reps: 10));

        $output = $this->finish($workout);

        self::assertFalse($output->isInProgress);
        self::assertNotNull($output->finishedAt);
        self::assertNull($this->useCase(GetCurrentWorkoutUseCase::class)->execute($this->idOf($this->alice)));
        // Finishing twice changes nothing: its moment is not rewritten.
        self::assertSame($output->finishedAt, $this->finish($workout)->finishedAt);
        self::assertTrue($this->start()->isInProgress);
    }

    /** Everything stays editable once finished: a forgotten set is added afterwards. */
    public function testAFinishedWorkoutStillTakesASet(): void
    {
        $workout = $this->getReference(WorkoutFixtures::ALICE_FINISHED, WorkoutDataModel::class);
        $output = $this->useCase(GetWorkoutUseCase::class)->execute($this->idOf($this->alice), $workout->id ?? 0);

        $output = $this->addSet($output, new AddWorkoutSetDataInput(reps: 6, weightInKilograms: 60.0));

        self::assertCount(5, $output->blocks[0]->exercises[0]->sets);
        // It was done already: a finished workout holds only done sets.
        self::assertTrue($output->blocks[0]->exercises[0]->sets[4]->isComplete);
        self::assertFalse($output->isInProgress);
    }

    /** During a workout a set is logged before it is done, and ticked once it is. */
    public function testASetIsLoggedUndoneThenTicked(): void
    {
        $workout = $this->addSet($this->addBlock($this->start(), MovementFixtures::PUSH_UP), new AddWorkoutSetDataInput(reps: 10));
        $setId = $workout->blocks[0]->exercises[0]->sets[0]->id;
        self::assertFalse($workout->blocks[0]->exercises[0]->sets[0]->isComplete);

        $this->useCase(CompleteWorkoutSetUseCase::class)->execute($this->idOf($this->alice), $workout->id, $setId);
        // Ticking twice changes nothing.
        $this->useCase(CompleteWorkoutSetUseCase::class)->execute($this->idOf($this->alice), $workout->id, $setId);

        // Read back from the database, not from what the use case held.
        $reread = $this->useCase(GetWorkoutUseCase::class)->execute($this->idOf($this->alice), $workout->id);
        self::assertTrue($reread->blocks[0]->exercises[0]->sets[0]->isComplete);

        $output = $this->useCase(UncompleteWorkoutSetUseCase::class)->execute($this->idOf($this->alice), $workout->id, $setId);
        self::assertFalse($output->blocks[0]->exercises[0]->sets[0]->isComplete);
    }

    /** A correction replaces the measures, not whether the set was done. */
    public function testACorrectedSetStaysTicked(): void
    {
        $workout = $this->addDoneSet($this->addBlock($this->start(), MovementFixtures::PUSH_UP), new AddWorkoutSetDataInput(reps: 10));

        $output = $this->useCase(UpdateWorkoutSetUseCase::class)->execute(
            $this->idOf($this->alice), $workout->id, $workout->blocks[0]->exercises[0]->sets[0]->id, new UpdateWorkoutSetDataInput(reps: 12),
        );

        self::assertTrue($output->blocks[0]->exercises[0]->sets[0]->isComplete);
    }

    public function testASetNotDoneHoldsTheWorkoutOpen(): void
    {
        $workout = $this->addDoneSet($this->addBlock($this->start(), MovementFixtures::PUSH_UP), new AddWorkoutSetDataInput(reps: 10));
        $workout = $this->addSet($workout, new AddWorkoutSetDataInput(reps: 8));

        try {
            $this->finish($workout);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(FinishWorkoutUseCase::ERROR_CODE, $exception->errorCode);
            self::assertSame([WorkoutFinishableConstraint::INCOMPLETE_SETS], $exception->violations['workout']);
        }
    }

    public function testAFinishedWorkoutHasNothingToTickOrUntick(): void
    {
        $workout = $this->getReference(WorkoutFixtures::ALICE_FINISHED, WorkoutDataModel::class);
        $output = $this->useCase(GetWorkoutUseCase::class)->execute($this->idOf($this->alice), $workout->id ?? 0);
        $setId = $output->blocks[0]->exercises[0]->sets[0]->id;

        foreach ([CompleteWorkoutSetUseCase::class, UncompleteWorkoutSetUseCase::class] as $useCase) {
            try {
                $this->useCase($useCase)->execute($this->idOf($this->alice), $output->id, $setId);
                self::fail('Expected ValidationException');
            } catch (ValidationException $exception) {
                self::assertSame($useCase::ERROR_CODE, $exception->errorCode);
                self::assertSame([WorkoutInProgressConstraint::FINISHED], $exception->violations['workout']);
            }
        }

        $reread = $this->useCase(GetWorkoutUseCase::class)->execute($this->idOf($this->alice), $output->id);
        self::assertTrue($reread->blocks[0]->exercises[0]->sets[0]->isComplete);
    }

    public function testFinishingKeepsTheWorkoutHabitAndDeletingUnkeepsIt(): void
    {
        $habit = $this->useCase(CreateHabitUseCase::class)->execute(new CreateHabitDataInput(
            'Une séance', HabitIconRegistry::DUMBBELL, HabitSourceRegistry::TRACKER, HabitTrackerRegistry::WORKOUT, 1,
        ));
        $this->useCase(SubscribeHabitUseCase::class)->execute($this->idOf($this->alice), $habit->id);

        $workout = $this->addDoneSet($this->addBlock($this->start(), MovementFixtures::PUSH_UP), new AddWorkoutSetDataInput(reps: 10));
        self::assertFalse($this->isKeptToday($habit->id));

        $this->finish($workout);
        self::assertTrue($this->isKeptToday($habit->id));

        $this->useCase(DeleteWorkoutUseCase::class)->execute($this->idOf($this->alice), $workout->id);
        self::assertFalse($this->isKeptToday($habit->id));
    }

    public function testAbandoningDeletesTheWorkoutInProgress(): void
    {
        $workout = $this->addSet($this->addBlock($this->start(), MovementFixtures::PUSH_UP), new AddWorkoutSetDataInput(reps: 10));

        $this->useCase(DeleteWorkoutUseCase::class)->execute($this->idOf($this->alice), $workout->id);

        self::assertNull(self::getContainer()->get(WorkoutProviderGateway::class)->findOneByIdForOwner($workout->id, $this->alice));
    }

    public function testTheHistoryListsFinishedWorkoutsOnly(): void
    {
        $this->start();

        $page = $this->useCase(ListWorkoutsUseCase::class)->execute($this->idOf($this->alice), new ListWorkoutsDataInput());

        self::assertSame(1, $page->total);
        $summary = $page->items[0];
        self::assertSame('Push', $summary->name);
        self::assertSame(['Bench press (barbell)', 'Push-up', 'Farmer walk (dumbbell)'], $summary->movementNames);
        self::assertSame(8, $summary->setCount);
    }

    public function testTheHistoryIsPaginated(): void
    {
        $workout = $this->addDoneSet($this->addBlock($this->start(), MovementFixtures::PUSH_UP), new AddWorkoutSetDataInput(reps: 10));
        $this->finish($workout);

        $page = $this->useCase(ListWorkoutsUseCase::class)->execute($this->idOf($this->alice), new ListWorkoutsDataInput(page: 1, perPage: 1));

        self::assertSame(2, $page->total);
        self::assertCount(1, $page->items);
        // The latest started first.
        self::assertSame($workout->id, $page->items[0]->id);
    }

    public function testItAnswersWhatEachMovementGaveTheLastTime(): void
    {
        $workout = $this->addBlock($this->start(), MovementFixtures::BENCH_PRESS_BARBELL);
        $workout = $this->addBlock($workout, MovementFixtures::PUSH_UP);
        $workout = $this->addBlock($workout, MovementFixtures::FARMER_WALK_DUMBBELL);

        $previous = $this->useCase(ListWorkoutPreviousPerformancesUseCase::class)->execute($this->idOf($this->alice), $workout->id);

        self::assertCount(3, $previous);
        self::assertSame($this->movementId(MovementFixtures::BENCH_PRESS_BARBELL), $previous[0]->movementId);
        self::assertSame([10, 8, 8, 7], array_map(static fn ($set) => $set->reps, $previous[0]->sets));
        self::assertSame([15, 12], array_map(static fn ($set) => $set->reps, $previous[1]->sets));
    }

    /** Read on the seeded workout itself, there is nothing before it. */
    /**
     * The seeded « Push »: four bench sets (one warm-up at 40 kg), then push-ups supersetted with
     * a farmer walk — a load carried over a distance, with no reps to multiply it by.
     */
    public function testItAddsAWorkoutUp(): void
    {
        $workout = $this->getReference(WorkoutFixtures::ALICE_FINISHED, WorkoutDataModel::class);

        $stats = $this->useCase(GetWorkoutStatsUseCase::class)->execute($this->idOf($this->alice), $workout->id ?? 0);

        self::assertSame(8, $stats->setCount);
        self::assertSame(1780.0, $stats->volumeInKilograms);
        self::assertNull($stats->durationInSeconds);
        self::assertSame(80, $stats->distanceInMetres);

        $shares = array_map(static fn ($muscle) => [$muscle->muscleName, $muscle->setShare, $muscle->percentage], $stats->muscles);
        self::assertSame([
            ['Mid chest', 6.0, 40.0],
            ['Front delts', 3.0, 20.0],
            ['Triceps', 3.0, 20.0],
            ['Full body', 2.0, 13.3],
            ['Abs', 1.0, 6.7],
        ], $shares);
    }

    public function testSomeoneElsesWorkoutHasNoStats(): void
    {
        $workout = $this->getReference(WorkoutFixtures::ALICE_FINISHED, WorkoutDataModel::class);

        $this->expectException(DataModelNotFoundException::class);

        $this->useCase(GetWorkoutStatsUseCase::class)->execute($this->idOf($this->bob), $workout->id ?? 0);
    }

    public function testTheFirstTimeHasNoLastTime(): void
    {
        $workout = $this->getReference(WorkoutFixtures::ALICE_FINISHED, WorkoutDataModel::class);

        self::assertSame([], $this->useCase(ListWorkoutPreviousPerformancesUseCase::class)->execute($this->idOf($this->alice), $workout->id ?? 0));
    }

    public function testSomeoneElsesWorkoutIsNotFound(): void
    {
        $workout = $this->getReference(WorkoutFixtures::ALICE_FINISHED, WorkoutDataModel::class);

        $this->expectException(DataModelNotFoundException::class);

        $this->useCase(GetWorkoutUseCase::class)->execute($this->idOf($this->bob), $workout->id ?? 0);
    }

    public function testASetOfAnotherWorkoutIsNotFound(): void
    {
        $seeded = $this->useCase(GetWorkoutUseCase::class)->execute(
            $this->idOf($this->alice), $this->getReference(WorkoutFixtures::ALICE_FINISHED, WorkoutDataModel::class)->id ?? 0,
        );
        $workout = $this->start();

        $this->expectException(DataModelNotFoundException::class);

        $this->useCase(DeleteWorkoutSetUseCase::class)->execute($this->idOf($this->alice), $workout->id, $seeded->blocks[0]->exercises[0]->sets[0]->id);
    }

    public function testThePickersOfferOnlyWhatIsLive(): void
    {
        $this->useCase(DeactivateMovementUseCase::class)->execute($this->movementId(MovementFixtures::PUSH_UP));
        $this->useCase(DeactivateSetTypeUseCase::class)->execute($this->setTypeId(SetTypeFixtures::DROPSET));

        $movements = array_map(static fn ($movement) => $movement->name, $this->useCase(ListWorkoutMovementsUseCase::class)->execute());
        $setTypes = array_map(static fn ($setType) => $setType->name, $this->useCase(ListWorkoutSetTypesUseCase::class)->execute());

        self::assertContains('Bench press (barbell)', $movements);
        self::assertNotContains('Push-up', $movements);
        self::assertSame(['Back-off', 'Échauffement', 'Échec'], $setTypes);
    }

    private function start(?string $name = null): WorkoutDataOutput
    {
        return $this->useCase(StartWorkoutUseCase::class)->execute($this->idOf($this->alice), new StartWorkoutDataInput($name));
    }

    private function addBlock(WorkoutDataOutput $workout, string ...$movementReferences): WorkoutDataOutput
    {
        $ids = array_values(array_map(fn (string $reference): int => $this->movementId($reference), $movementReferences));

        return $this->useCase(AddWorkoutBlockUseCase::class)->execute($this->idOf($this->alice), $workout->id, new AddWorkoutBlockDataInput($ids));
    }

    /** On the first movement of the first block. */
    private function addSet(WorkoutDataOutput $workout, AddWorkoutSetDataInput $input): WorkoutDataOutput
    {
        return $this->useCase(AddWorkoutSetUseCase::class)->execute(
            $this->idOf($this->alice), $workout->id, $workout->blocks[0]->exercises[0]->id, $input,
        );
    }

    /** On the first movement of the first block, then ticked as done: the last set there. */
    private function addDoneSet(WorkoutDataOutput $workout, AddWorkoutSetDataInput $input): WorkoutDataOutput
    {
        $workout = $this->addSet($workout, $input);
        $set = $workout->blocks[0]->exercises[0]->sets[array_key_last($workout->blocks[0]->exercises[0]->sets)];

        return $this->useCase(CompleteWorkoutSetUseCase::class)->execute($this->idOf($this->alice), $workout->id, $set->id);
    }

    private function finish(WorkoutDataOutput $workout): WorkoutDataOutput
    {
        return $this->useCase(FinishWorkoutUseCase::class)->execute($this->idOf($this->alice), $workout->id);
    }

    /** @return list<string> */
    private function movementNames(WorkoutDataOutput $workout, int $blockIndex): array
    {
        return array_map(static fn ($exercise) => $exercise->movement->name, $workout->blocks[$blockIndex]->exercises);
    }

    private function isKeptToday(int $habitId): bool
    {
        foreach ($this->useCase(ListHabitsUseCase::class)->execute($this->idOf($this->alice)) as $habit) {
            if ($habitId === $habit->habitId) {
                return $habit->isCompletedToday;
            }
        }

        throw new LogicException('The habit is not in the list.');
    }

    private function movementId(string $reference): int
    {
        return $this->getReference($reference, MovementDataModel::class)->id ?? 0;
    }

    private function setTypeId(string $reference): int
    {
        return $this->getReference($reference, SetTypeDataModel::class)->id ?? 0;
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    private function useCase(string $class): object
    {
        return self::getContainer()->get($class);
    }

    private function idOf(UserDataModel $user): int
    {
        if (null === $user->id) {
            throw new LogicException('The seeded account has no id.');
        }

        return $user->id;
    }
}
