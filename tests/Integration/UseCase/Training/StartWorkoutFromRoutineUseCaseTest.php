<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Event\Training\WorkoutCreatedEvent;
use App\Domain\DTO\Event\Training\WorkoutSetCreatedEvent;
use App\Domain\DTO\Input\Training\AddRoutineSetDataInput;
use App\Domain\DTO\Output\Training\WorkoutCopyDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Domain\Validation\Constraint\Training\RoutineGivesSetsConstraint;
use App\Domain\Validation\Constraint\Training\WorkoutNotInProgressConstraint;
use App\Domain\Validation\Validator\Training\StartWorkoutFromRoutineValidator;
use App\Fixtures\Training\MovementFixtures;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\RecordedEventsTrait;
use App\UseCase\Training\AddRoutineSetUseCase;
use App\UseCase\Training\Admin\DeactivateMovementUseCase;
use App\UseCase\Training\GetCurrentWorkoutUseCase;
use App\UseCase\Training\StartWorkoutFromRoutineUseCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Starting a workout from a routine: its layout, every set still to do, each load worked out. */
final class StartWorkoutFromRoutineUseCaseTest extends KernelTestCase
{
    use QueryCountTrait;
    use RoutineTestTrait;
    use RecordedEventsTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadWorkoutFixtures();
    }

    /** 80 % of the bench's estimated 1RM of 76 kg, rounded down: 60 kg. A push-up has no load to give. */
    public function testItStartsAWorkoutLaidOutLikeTheRoutine(): void
    {
        $routine = $this->addRoutineBlock($this->benchRoutine(), MovementFixtures::PUSH_UP);
        $routine = $this->useCase(AddRoutineSetUseCase::class)->execute(
            $this->currentUser($this->aliceId), $routine->id, $routine->blocks[1]->exercises[0]->id, new AddRoutineSetDataInput(12, null),
        );

        $output = $this->startFrom($routine->id);
        $workout = $output->workout;

        self::assertSame([], $output->skippedMovements);
        self::assertMatchesRegularExpression('#^Push · \d{2}/\d{2}/\d{4}$#', (string) $workout->name);
        self::assertTrue($workout->isInProgress);
        self::assertSame($routine->id, $workout->routineId);
        self::assertSame(['Bench press (barbell)'], $this->movementNames($workout, 0));
        self::assertSame(['Push-up'], $this->movementNames($workout, 1));

        $bench = $workout->blocks[0]->exercises[0]->sets[0];
        self::assertSame([5, 60.0, false, 'Travail'], [$bench->reps, $bench->weightInKilograms, $bench->isComplete, $bench->setType->name]);
        $pushUp = $workout->blocks[1]->exercises[0]->sets[0];
        self::assertSame([12, null], [$pushUp->reps, $pushUp->weightInKilograms]);

        // Read back: it is the workout in progress, and it remembers its routine.
        self::getContainer()->get(EntityManagerInterface::class)->clear();
        $current = $this->useCase(GetCurrentWorkoutUseCase::class)->execute($this->currentUser($this->aliceId));
        self::assertSame($workout->id, $current?->id);
        self::assertSame($routine->id, $current->routineId);
        self::assertSame(60.0, $current->blocks[0]->exercises[0]->sets[0]->weightInKilograms);
    }

    /** Never done with a load, never estimated: the set waits for its load. */
    public function testAMovementWithoutAnEstimatedOneRepMaxGivesSetsWithoutALoad(): void
    {
        $routine = $this->addRoutineSet($this->addRoutineBlock($this->createRoutine(), MovementFixtures::BENCH_PRESS_DUMBBELL), new AddRoutineSetDataInput(8, 70.0));

        $set = $this->startFrom($routine->id)->workout->blocks[0]->exercises[0]->sets[0];

        self::assertSame([8, null], [$set->reps, $set->weightInKilograms]);
    }

    public function testAStartLeavesOutWhatIsNoLongerOffered(): void
    {
        $routine = $this->addRoutineBlock($this->benchRoutine(), MovementFixtures::PUSH_UP);
        $this->useCase(DeactivateMovementUseCase::class)->execute($this->currentAdmin(), $this->movementId(MovementFixtures::PUSH_UP));

        $output = $this->startFrom($routine->id);

        self::assertSame(['Push-up'], $output->skippedMovements);
        self::assertCount(1, $output->workout->blocks);
    }

    public function testARoutineWithoutASetCannotBeStarted(): void
    {
        $routine = $this->addRoutineBlock($this->createRoutine(), MovementFixtures::BENCH_PRESS_BARBELL);

        $this->assertRefused(['routine' => [RoutineGivesSetsConstraint::EMPTY_ROUTINE]], $routine->id);
    }

    public function testARoutineWhoseEveryMovementWasRetiredCannotBeStarted(): void
    {
        $routine = $this->benchRoutine();
        $this->useCase(DeactivateMovementUseCase::class)->execute($this->currentAdmin(), $this->movementId(MovementFixtures::BENCH_PRESS_BARBELL));

        $this->assertRefused(['routine' => [RoutineGivesSetsConstraint::EMPTY_ROUTINE]], $routine->id);
    }

    public function testAStartIsRefusedWhileAWorkoutIsInProgress(): void
    {
        $routine = $this->benchRoutine();
        $this->start();

        $this->assertRefused(['workout' => [WorkoutNotInProgressConstraint::ALREADY_IN_PROGRESS]], $routine->id);
    }

    public function testSomeoneElsesRoutineCannotBeStarted(): void
    {
        $routine = $this->benchRoutine();

        try {
            $this->useCase(StartWorkoutFromRoutineUseCase::class)->execute($this->currentUser($this->idOf($this->bob)), $routine->id);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(['currentUser' => [IsOwnerConstraint::NOT_ALLOWED]], $exception->violations);
        }
    }

    public function testAnUnknownRoutineIsNotFound(): void
    {
        $this->expectException(DataModelNotFoundException::class);

        $this->startFrom(123456789);
    }

    /** Its summary is its handler's to create: the workout is written whole, and said once. */
    public function testStartingFromARoutineRaisesItsEvent(): void
    {
        $workout = $this->startFrom($this->benchRoutine()->id)->workout;

        self::assertSame($workout->id, $this->assertEventRaised(WorkoutCreatedEvent::class)->workout->id);
        $this->assertNoEventRaised(WorkoutSetCreatedEvent::class);
    }

    public function testStartingFromARoutineStaysWithinItsQueryBudget(): void
    {
        $routineId = $this->addRoutineBlock($this->benchRoutine(), MovementFixtures::PUSH_UP)->id;

        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($this->aliceId),
            fn (UserDataModel $currentUser) => $this->useCase(StartWorkoutFromRoutineUseCase::class)->execute($currentUser, $routineId),
            // Four reads — the routine, the default set type, the estimated 1RMs, the workout in
            // progress — then one INSERT per row: the workout, 2 blocks, 2 movements, 1 set.
            max: 10,
        );
    }

    private function startFrom(int $routineId): WorkoutCopyDataOutput
    {
        return $this->useCase(StartWorkoutFromRoutineUseCase::class)->execute($this->currentUser($this->aliceId), $routineId);
    }

    /** @param array<string, list<string>> $violations */
    private function assertRefused(array $violations, int $routineId): void
    {
        try {
            $this->startFrom($routineId);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(StartWorkoutFromRoutineValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame($violations, $exception->violations);
        }
    }
}
