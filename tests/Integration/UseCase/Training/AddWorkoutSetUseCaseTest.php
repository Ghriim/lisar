<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training;

use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Event\Training\WorkoutSetCreatedEvent;
use App\Domain\DTO\Input\Training\AddWorkoutSetDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Training\WorkoutSetMeasuresConstraint;
use App\Domain\Validation\Constraint\Training\WorkoutSetTypeUsableConstraint;
use App\Domain\Validation\Constraint\Training\WorkoutSetWeightPresentConstraint;
use App\Domain\Validation\Validator\Training\AddWorkoutSetValidator;
use App\Fixtures\Training\MovementFixtures;
use App\Fixtures\Training\SetTypeFixtures;
use App\Fixtures\Training\WorkoutFixtures;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\RecordedEventsTrait;
use App\UseCase\Training\AddWorkoutSetUseCase;
use App\UseCase\Training\Admin\DeactivateSetTypeUseCase;
use App\UseCase\Training\GetWorkoutUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Logging a set on a movement of a workout, with the measures that movement tracks. */
final class AddWorkoutSetUseCaseTest extends KernelTestCase
{
    use QueryCountTrait;
    use WorkoutTestTrait;
    use RecordedEventsTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadWorkoutFixtures();
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
        self::assertSame('Dropset', $set->setType->name);

        // Read back from the database, not from what the use case held.
        $reread = $this->useCase(GetWorkoutUseCase::class)->execute($this->currentUser($this->idOf($this->alice)), $workout->id);
        self::assertSame(62.5, $reread->blocks[0]->exercises[0]->sets[0]->weightInKilograms);
    }

    /** The column keeps what it is handed: the use case is what keeps two decimals of a load. */
    public function testItKeepsTwoDecimalsOfALoad(): void
    {
        $workout = $this->addBlock($this->start(), MovementFixtures::BENCH_PRESS_BARBELL);

        $output = $this->addSet($workout, new AddWorkoutSetDataInput(reps: 8, weightInKilograms: 62.456));

        self::assertSame(62.46, $output->blocks[0]->exercises[0]->sets[0]->weightInKilograms);
    }

    public function testASetMissesNoTrackedMeasureAndCarriesNoOther(): void
    {
        $workout = $this->addBlock($this->start(), MovementFixtures::BENCH_PRESS_BARBELL);

        try {
            $this->addSet($workout, new AddWorkoutSetDataInput(weightInKilograms: 60.0, distanceInMetres: 100));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(AddWorkoutSetValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame([WorkoutSetMeasuresConstraint::REPS_REQUIRED], $exception->violations['reps']);
            self::assertSame([WorkoutSetMeasuresConstraint::DISTANCE_NOT_TRACKED], $exception->violations['distanceInMetres']);
        }
    }

    /** While the workout runs, a set may wait for its load: it is still to do. */
    public function testASetStillToDoMayWaitForItsLoad(): void
    {
        $output = $this->addSet($this->addBlock($this->start(), MovementFixtures::BENCH_PRESS_BARBELL), new AddWorkoutSetDataInput(reps: 5));

        $reread = $this->useCase(GetWorkoutUseCase::class)->execute($this->currentUser($this->idOf($this->alice)), $output->id);
        self::assertSame(5, $reread->blocks[0]->exercises[0]->sets[0]->reps);
        self::assertNull($reread->blocks[0]->exercises[0]->sets[0]->weightInKilograms);
    }

    /** A set added to a finished workout comes ticked: it needs its load at once. */
    public function testAFinishedWorkoutTakesNoSetWithoutItsLoad(): void
    {
        $workout = $this->getReference(WorkoutFixtures::ALICE_FINISHED, WorkoutDataModel::class);
        $output = $this->useCase(GetWorkoutUseCase::class)->execute($this->currentUser($this->idOf($this->alice)), $workout->id ?? 0);

        try {
            $this->addSet($output, new AddWorkoutSetDataInput(reps: 6));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(AddWorkoutSetValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame([WorkoutSetWeightPresentConstraint::WEIGHT_REQUIRED], $exception->violations['weightInKilograms']);
        }
    }

    public function testASetRefusesARetiredSetType(): void
    {
        $workout = $this->addBlock($this->start(), MovementFixtures::PUSH_UP);
        $this->useCase(DeactivateSetTypeUseCase::class)->execute($this->currentAdmin(), $this->setTypeId(SetTypeFixtures::DROPSET));

        try {
            $this->addSet($workout, new AddWorkoutSetDataInput(reps: 10, setTypeId: $this->setTypeId(SetTypeFixtures::DROPSET)));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame([WorkoutSetTypeUsableConstraint::INACTIVE], $exception->violations['setTypeId']);
        }
    }

    /** Everything stays editable once finished: a forgotten set is added afterwards. */
    public function testAFinishedWorkoutStillTakesASet(): void
    {
        $workout = $this->getReference(WorkoutFixtures::ALICE_FINISHED, WorkoutDataModel::class);
        $output = $this->useCase(GetWorkoutUseCase::class)->execute($this->currentUser($this->idOf($this->alice)), $workout->id ?? 0);

        $output = $this->addSet($output, new AddWorkoutSetDataInput(reps: 6, weightInKilograms: 60.0));

        self::assertCount(5, $output->blocks[0]->exercises[0]->sets);
        // It was done already: a finished workout holds only done sets.
        self::assertTrue($output->blocks[0]->exercises[0]->sets[4]->isComplete);
        self::assertFalse($output->isInProgress);
    }

    /** What a logged set moves — the records, its workout's summary — is its handlers' to test. */
    public function testLoggingASetRaisesItsEvent(): void
    {
        $workout = $this->addSet($this->benchWorkout(), new AddWorkoutSetDataInput(reps: 5, weightInKilograms: 70.0));

        self::assertSame($this->lastSet($workout)->id, $this->assertEventRaised(WorkoutSetCreatedEvent::class)->set->id);
    }

    public function testAddingASetStaysWithinItsQueryBudget(): void
    {
        $workout = $this->inProgressPush();
        $exerciseId = $workout->blocks[0]->exercises[0]->id;

        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($this->idOf($this->alice)),
            fn (UserDataModel $currentUser) => $this->useCase(AddWorkoutSetUseCase::class)->execute($currentUser, $workout->id, $exerciseId, new AddWorkoutSetDataInput(reps: 5, weightInKilograms: 70.0)),
        );
    }
}
